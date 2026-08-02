<?php

namespace Tests\Feature;

use App\Models\DailyOperationsReportRun;
use App\Models\User;
use App\Modules\DailyOperationsReports\Actions\CreateDailyOperationsReport;
use App\Modules\DailyOperationsReports\Actions\PruneDailyOperationsReports;
use App\Modules\DailyOperationsReports\Actions\QueueScheduledDailyOperationsReports;
use App\Modules\DailyOperationsReports\Actions\StartDailyOperationsReport;
use App\Modules\DailyOperationsReports\Jobs\CreateDailyOperationsReportJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class DailyOperationsReportArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_archive_is_card_based_localized_and_portfolio_scoped(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $foreignPortfolio->update(['owner_user_id' => $foreignOwner->id]);
        $own = $this->completedRun($owner, $portfolio->id);
        $foreign = $this->completedRun($foreignOwner, $foreignPortfolio->id);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('reports.daily-operations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/daily-operations/index')
                ->has('reports.data', 1)
                ->where('reports.data.0.id', $own->id)
                ->where('reports.data.0.status_label', 'مكتملة')
                ->where('reports.data.0.scope_label', $portfolio->name_ar)
                ->where('summary.completed', 1)
                ->where('canSelectGlobal', false));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get('/documentation/daily-operations-archive')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/documentation/show')
                ->where('guide.title', 'أرشيف العمليات اليومية')
                ->where('guide.route', '/reports/daily-operations'));

        $this->actingAs($owner)
            ->get(route('reports.daily-operations.show', $foreign))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('reports.daily-operations.index'))
            ->assertForbidden();

        $this->actingAs($tenant)
            ->get(route('reports.daily-operations.index'))
            ->assertForbidden();
    }

    public function test_owner_generates_real_private_files_and_downloads_only_their_scope(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $run = app(StartDailyOperationsReport::class)->create($owner);

        $completed = app(CreateDailyOperationsReport::class)->handle($run->id);

        $this->assertSame('completed', $completed->status);
        $this->assertSame($portfolio->id, $completed->portfolio_id);
        $this->assertSame('%PDF-', substr(Storage::disk('local')->get((string) $completed->pdf_path), 0, 5));
        $this->assertSame('PK', substr(Storage::disk('local')->get((string) $completed->docx_path), 0, 2));
        $this->assertSame('PK', substr(Storage::disk('local')->get((string) $completed->xlsx_path), 0, 2));

        foreach (['pdf', 'docx', 'xlsx'] as $format) {
            $this->actingAs($owner)
                ->get(route('reports.daily-operations.download', [
                    'dailyOperationsReportRun' => $completed,
                    'format' => $format,
                ]))
                ->assertOk()
                ->assertDownload(basename((string) $completed->{$format.'_path'}));

            $this->actingAs($foreignOwner)
                ->get(route('reports.daily-operations.download', [
                    'dailyOperationsReportRun' => $completed,
                    'format' => $format,
                ]))
                ->assertForbidden();
        }
    }

    public function test_manual_request_is_queued_and_superadmin_can_choose_global_scope(): void
    {
        Queue::fake();
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->post(route('reports.daily-operations.store'), ['portfolio_id' => null])
            ->assertRedirect(route('reports.daily-operations.index'))
            ->assertSessionHas('success');

        $run = DailyOperationsReportRun::query()->sole();
        $this->assertNull($run->portfolio_id);
        $this->assertSame('manual', $run->trigger);
        Queue::assertPushed(
            CreateDailyOperationsReportJob::class,
            fn (CreateDailyOperationsReportJob $job): bool => $job->runId === $run->id,
        );

        $this->actingAs($superadmin)
            ->post(route('reports.daily-operations.store'))
            ->assertSessionHasErrors('report');
    }

    public function test_scheduled_generation_is_idempotent_for_global_and_owner_scopes(): void
    {
        Queue::fake();
        $superadmin = $this->createUserWithRole('superadmin');
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);

        $first = app(QueueScheduledDailyOperationsReports::class)->handle();
        $second = app(QueueScheduledDailyOperationsReports::class)->handle();

        $this->assertSame(['queued' => 2, 'skipped' => 0], $first);
        $this->assertSame(['queued' => 0, 'skipped' => 0], $second);
        $this->assertDatabaseCount('daily_operations_report_runs', 2);
        $this->assertDatabaseHas('daily_operations_report_runs', [
            'initiated_by_user_id' => $superadmin->id,
            'portfolio_id' => null,
            'trigger' => 'scheduled',
        ]);
        $this->assertDatabaseHas('daily_operations_report_runs', [
            'initiated_by_user_id' => $owner->id,
            'portfolio_id' => $portfolio->id,
            'trigger' => 'scheduled',
        ]);
        Queue::assertPushed(CreateDailyOperationsReportJob::class, 2);
    }

    public function test_retention_prunes_private_files_but_keeps_the_audit_row(): void
    {
        config()->set('operations.daily_report_retention_days', 30);
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $run = $this->completedRun($owner, $portfolio->id, today()->subDays(31)->toDateString());

        $count = app(PruneDailyOperationsReports::class)->handle();

        $this->assertSame(1, $count);
        $this->assertSame('pruned', $run->refresh()->status);
        $this->assertNull($run->pdf_path);
        $this->assertFalse(Storage::disk('local')->exists("daily-reports/{$run->id}.pdf"));
        $this->assertDatabaseHas('daily_operations_report_runs', ['id' => $run->id]);
    }

    public function test_failed_report_audit_cannot_be_pruned_through_the_download_control(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $run = DailyOperationsReportRun::query()->create([
            'portfolio_id' => $portfolio->id,
            'initiated_by_user_id' => $owner->id,
            'status' => 'failed',
            'trigger' => 'manual',
            'report_date' => today(),
            'storage_disk' => 'local',
            'failure_summary' => 'Safe test failure',
            'failed_at' => now(),
        ]);

        $this->actingAs($owner)
            ->delete(route('reports.daily-operations.destroy', $run))
            ->assertUnprocessable();

        $this->assertSame('failed', $run->refresh()->status);
        $this->assertSame('Safe test failure', $run->failure_summary);
    }

    private function completedRun(
        User $actor,
        ?int $portfolioId,
        ?string $reportDate = null,
    ): DailyOperationsReportRun {
        $id = DailyOperationsReportRun::query()->max('id') + 1;
        $paths = [
            'pdf' => "daily-reports/{$id}.pdf",
            'docx' => "daily-reports/{$id}.docx",
            'xlsx' => "daily-reports/{$id}.xlsx",
        ];

        Storage::disk('local')->put($paths['pdf'], '%PDF-report');
        Storage::disk('local')->put($paths['docx'], 'PK-word');
        Storage::disk('local')->put($paths['xlsx'], 'PK-workbook');

        return DailyOperationsReportRun::query()->create([
            'portfolio_id' => $portfolioId,
            'initiated_by_user_id' => $actor->id,
            'status' => 'completed',
            'trigger' => 'manual',
            'report_date' => $reportDate ?? today()->toDateString(),
            'storage_disk' => 'local',
            'pdf_path' => $paths['pdf'],
            'docx_path' => $paths['docx'],
            'xlsx_path' => $paths['xlsx'],
            'pdf_bytes' => 11,
            'docx_bytes' => 7,
            'xlsx_bytes' => 11,
            'item_count' => 4,
            'summary_json' => [
                'priority' => [
                    'total' => 4,
                    'critical' => 1,
                    'high' => 1,
                    'normal' => 2,
                    'unassigned' => 1,
                ],
                'types' => [],
                'currencies' => [],
            ],
            'scope_json' => [],
            'completed_at' => now(),
        ]);
    }
}
