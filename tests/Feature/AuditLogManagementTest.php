<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_only_see_and_export_their_portfolio_activity(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Own Audit Unit']);
        $foreignAsset = $this->createAsset($foreignPortfolio, ['title_en' => 'Foreign Audit Unit']);

        Activity::query()->delete();

        Activity::query()->create([
            'log_name' => 'default',
            'description' => 'Own asset updated',
            'subject_type' => 'asset',
            'subject_id' => $asset->id,
            'event' => 'updated',
            'causer_type' => 'user',
            'causer_id' => $owner->id,
            'attribute_changes' => [
                'attributes' => ['title_en' => 'Own Audit Unit'],
                'old' => ['title_en' => 'Old Unit'],
            ],
        ]);
        Activity::query()->create([
            'log_name' => 'default',
            'description' => 'Foreign asset updated',
            'subject_type' => Asset::class,
            'subject_id' => $foreignAsset->id,
            'event' => 'updated',
            'causer_type' => User::class,
            'causer_id' => $foreignOwner->id,
            'attribute_changes' => [
                'attributes' => ['title_en' => 'Foreign Audit Unit'],
                'old' => ['title_en' => 'Old Foreign Unit'],
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('Own Audit Unit')
            ->assertDontSee('Foreign Audit Unit');

        $export = $this->actingAs($owner)
            ->get(route('audit-logs.export'))
            ->assertOk();

        $sheetXml = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('Own Audit Unit', $sheetXml);
        $this->assertStringNotContainsString('Foreign Audit Unit', $sheetXml);
    }

    public function test_superadmin_can_filter_audit_logs_by_portfolio(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $asset = $this->createAsset($portfolio, ['title_en' => 'Selected Portfolio Asset']);
        $foreignAsset = $this->createAsset($foreignPortfolio, ['title_en' => 'Other Portfolio Asset']);

        Activity::query()->delete();

        Activity::query()->create([
            'description' => 'Selected activity',
            'subject_type' => 'asset',
            'subject_id' => $asset->id,
            'event' => 'updated',
        ]);
        Activity::query()->create([
            'description' => 'Other activity',
            'subject_type' => 'asset',
            'subject_id' => $foreignAsset->id,
            'event' => 'updated',
        ]);

        $this->actingAs($superadmin)
            ->get(route('audit-logs.index', ['portfolio_id' => $portfolio->id]))
            ->assertOk()
            ->assertSee('Selected Portfolio Asset')
            ->assertDontSee('Other Portfolio Asset');
    }

    public function test_tenant_cannot_access_audit_logs(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        $this->actingAs($tenant)
            ->get(route('audit-logs.index'))
            ->assertForbidden();

        $this->actingAs($tenant)
            ->get(route('audit-logs.export'))
            ->assertForbidden();
    }

    public function test_sensitive_user_fields_are_not_logged(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        Activity::query()->delete();

        $this->actingAs($owner);
        $owner->update([
            'name' => 'Owner Updated',
            'password' => 'new-local-password',
            'remember_token' => 'secret-token',
        ]);

        $activity = Activity::query()->latest()->firstOrFail();
        $attributes = $activity->attribute_changes?->get('attributes') ?? [];

        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayNotHasKey('password', $attributes);
        $this->assertArrayNotHasKey('remember_token', $attributes);
    }

    public function test_audit_filters_reject_invalid_dates_and_foreign_owner_scope(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);

        $this->actingAs($owner)
            ->from(route('audit-logs.index'))
            ->get(route('audit-logs.index', ['date_from' => 'not-a-date']))
            ->assertRedirect(route('audit-logs.index'))
            ->assertSessionHasErrors('date_from');

        $this->actingAs($owner)
            ->from(route('audit-logs.index'))
            ->get(route('audit-logs.index', [
                'date_from' => '2026-07-21',
                'date_to' => '2026-07-20',
            ]))
            ->assertRedirect(route('audit-logs.index'))
            ->assertSessionHasErrors('date_to');

        $this->actingAs($owner)
            ->get(route('audit-logs.index', ['date_to' => '2026-07-21']))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('audit-logs.index', ['portfolio_id' => $foreignPortfolio->id]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('audit-logs.index', ['causer_id' => $foreignOwner->id]))
            ->assertForbidden();
    }

    public function test_audit_workspace_returns_localized_rows_metrics_and_record_links(): void
    {
        $portfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $asset = $this->createAsset($portfolio, [
            'title_en' => 'English Audit Asset',
            'title_ar' => 'عقار سجل التدقيق',
        ]);

        Activity::query()->delete();
        Activity::query()->create([
            'log_name' => 'default',
            'description' => 'Asset record updated',
            'subject_type' => Asset::class,
            'subject_id' => $asset->id,
            'event' => 'updated',
            'causer_type' => User::class,
            'causer_id' => $superadmin->id,
            'attribute_changes' => [
                'attributes' => [
                    'title_en' => 'English Audit Asset',
                    'password' => 'must-never-appear',
                    'api_token' => 'must-never-appear',
                ],
            ],
        ]);

        $this->actingAs($superadmin)
            ->get(route('audit-logs.index', [
                'locale' => 'ar',
                'event' => 'updated',
                'per_page' => 25,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/audit/index')
                ->where('filters.event', 'updated')
                ->where('filters.per_page', 25)
                ->where('auditInsights.total', 1)
                ->where('auditInsights.updated', 1)
                ->has('activities.data', 1)
                ->where('activities.data.0.event_label', 'تحديث')
                ->where('activities.data.0.subject_type_label', 'أصل عقاري')
                ->where('activities.data.0.subject_label', 'عقار سجل التدقيق')
                ->where('activities.data.0.subject_url', route('assets.show', $asset))
                ->where('activities.data.0.changed_keys', ['title_en'])
                ->where('activities.data.0.changed_count', 1)
                ->where('counts.0.value', 1)
                ->where('counts.2.value', 1)
            );
    }

    public function test_event_counts_respect_non_event_filters_and_pagination(): void
    {
        $portfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $asset = $this->createAsset($portfolio, ['title_en' => 'Counted Audit Asset']);

        Activity::query()->delete();

        foreach (range(1, 12) as $index) {
            Activity::query()->create([
                'description' => "Counted create {$index}",
                'subject_type' => 'asset',
                'subject_id' => $asset->id,
                'event' => 'created',
                'created_at' => '2026-07-20 10:00:00',
                'updated_at' => '2026-07-20 10:00:00',
            ]);
        }

        foreach (range(1, 3) as $index) {
            Activity::query()->create([
                'description' => "Counted update {$index}",
                'subject_type' => 'asset',
                'subject_id' => $asset->id,
                'event' => 'updated',
                'created_at' => '2026-07-20 11:00:00',
                'updated_at' => '2026-07-20 11:00:00',
            ]);
        }

        $this->actingAs($superadmin)
            ->get(route('audit-logs.index', [
                'portfolio_id' => $portfolio->id,
                'event' => 'created',
                'date_from' => '2026-07-20',
                'date_to' => '2026-07-20',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activities.total', 12)
                ->where('activities.per_page', 10)
                ->has('activities.data', 10)
                ->where('counts.0.value', 15)
                ->where('counts.1.value', 12)
                ->where('counts.2.value', 3)
                ->where('counts.3.value', 0)
            );
    }

    public function test_arabic_audit_export_is_a_real_localized_workbook(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $asset = $this->createAsset($portfolio, ['title_ar' => 'عقار للتصدير']);

        Activity::query()->delete();
        Activity::query()->create([
            'description' => 'Export activity',
            'subject_type' => 'asset',
            'subject_id' => $asset->id,
            'event' => 'created',
        ]);

        $export = $this->actingAs($owner)
            ->get(route('audit-logs.export', ['locale' => 'ar']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $sheetXml = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('السجل المتأثر', $sheetXml);
        $this->assertStringContainsString('عقار للتصدير', $sheetXml);
    }
}
