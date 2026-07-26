<?php

namespace Tests\Feature;

use App\Models\AssetStakeholder;
use App\Models\OperationalReadinessCheck;
use App\Models\ShowcaseDataset;
use App\Modules\SystemReadiness\Actions\RecordSchedulerHeartbeat;
use App\Modules\SystemReadiness\Notifications\ReadinessTestNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\PendingCommand;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SystemReadinessWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_only_superadmin_can_open_the_launch_readiness_workspace(): void
    {
        $portfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'parent_id' => null,
            'rentable' => false,
        ]);

        foreach ([
            ['relationship_type' => 'owner', 'user_id' => $owner->id],
            ['relationship_type' => 'manager', 'user_id' => $manager->id],
        ] as $stakeholder) {
            AssetStakeholder::query()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $property->id,
                'is_primary' => true,
                ...$stakeholder,
            ]);
        }

        $this->recordSchedulerCadence();

        $this->actingAs($superadmin)
            ->get(route('system-readiness.index', ['portfolio_id' => $portfolio->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/system-readiness/index')
                ->has('systemChecks', 7)
                ->has('systemConfirmations', 4)
                ->has('portfolioConfirmations', 6)
                ->where('summary.total', 25)
                ->where('portfolioLaunch.live_portfolios', 1)
                ->where('portfolioLaunch.needs_live_portfolio', false)
                ->where('portfolioReadiness.portfolio.id', $portfolio->id)
                ->where('portfolioReadiness.metrics.owners', 1)
                ->where('portfolioReadiness.metrics.managers', 1)
                ->where('portfolioReadiness.metrics.tenants', 1)
                ->where('portfolioReadiness.metrics.properties', 1)
                ->where('portfolioReadiness.metrics.assignment_gaps', 0)
                ->where('systemChecks', fn (mixed $checks): bool => ($this->systemCheck($checks, 'scheduler')['status'] ?? null) === 'ready'));

        $this->actingAs($owner)
            ->get(route('system-readiness.index'))
            ->assertForbidden();

        $this->actingAs($tenant)
            ->get(route('system-readiness.index'))
            ->assertForbidden();
    }

    public function test_readiness_exposes_a_direct_live_portfolio_launch_path(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->get(route('system-readiness.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('portfolioLaunch.live_portfolios', 0)
                ->where('portfolioLaunch.needs_live_portfolio', true)
                ->where('portfolioLaunch.create_href', route('portfolios.create'))
                ->where('portfolioReadiness', null));
    }

    public function test_showcase_and_archived_portfolios_do_not_replace_the_live_launch_path(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $dataset = ShowcaseDataset::query()->create([
            'key' => 'READINESS-LIVE-GATE',
            'name' => 'Readiness live gate',
            'status' => 'completed',
            'target_properties' => 1,
            'generated_properties' => 1,
        ]);
        $showcase = $this->createPortfolio([
            'showcase_dataset_id' => $dataset->id,
        ]);

        $this->createPortfolio(['status' => 'archived']);

        $this->actingAs($superadmin)
            ->get(route('system-readiness.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('portfolioLaunch.live_portfolios', 0)
                ->where('portfolioLaunch.needs_live_portfolio', true)
                ->where('portfolioReadiness', null)
                ->has('portfolioOptions', 1)
                ->where('portfolioOptions.0.id', $showcase->id)
                ->where('portfolioOptions.0.is_showcase', true)
                ->where(
                    'app.translations.readiness.select_portfolio',
                    'Select a portfolio to review',
                ));
    }

    public function test_portfolio_blockers_open_exact_scoped_setup_actions(): void
    {
        $portfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->get(route('system-readiness.index', ['portfolio_id' => $portfolio->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('portfolioReadiness.checks', function (mixed $checks) use ($portfolio): bool {
                    $owner = $this->systemCheck($checks, 'portfolio_owner');
                    $manager = $this->systemCheck($checks, 'operations_team');
                    $property = $this->systemCheck($checks, 'property_register');
                    $tenant = $this->systemCheck($checks, 'tenant_access');
                    $showcase = $this->systemCheck($checks, 'showcase');

                    return ($owner['href'] ?? null) === route('users.create', [
                        'portfolio_id' => $portfolio->id,
                        'role' => 'owner',
                    ])
                        && ($owner['action_label'] ?? null) === 'Create owner'
                        && ($manager['href'] ?? null) === route('users.create', [
                            'portfolio_id' => $portfolio->id,
                            'role' => 'property_manager',
                        ])
                        && ($manager['action_label'] ?? null) === 'Create manager'
                        && ($property['href'] ?? null) === route('assets.structure.create', [
                            'portfolio_id' => $portfolio->id,
                        ])
                        && ($property['action_label'] ?? null) === 'Set up building'
                        && ($tenant['href'] ?? null) === route('tenants.create', [
                            'portfolio_id' => $portfolio->id,
                            'next' => 'lease',
                        ])
                        && ($tenant['action_label'] ?? null) === 'Onboard tenant'
                        && ($showcase['href'] ?? null) === route('portfolios.show', $portfolio);
                }));
    }

    public function test_inactive_owner_readiness_action_is_localized_and_opens_the_owner_record(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['status' => 'inactive']);
        $superadmin = $this->createUserWithRole('superadmin');
        $portfolio->update(['owner_user_id' => $owner->id]);

        $this->actingAs($superadmin)
            ->get(route('system-readiness.index', ['portfolio_id' => $portfolio->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('portfolioReadiness.checks', function (mixed $checks) use ($owner): bool {
                    $ownerCheck = $this->systemCheck($checks, 'portfolio_owner');

                    return ($ownerCheck['href'] ?? null) === route('users.edit', $owner)
                        && ($ownerCheck['action_label'] ?? null) === 'Configure owner';
                }));

        $this->actingAs($superadmin)
            ->withSession(['locale' => 'ar'])
            ->get(route('system-readiness.index', ['portfolio_id' => $portfolio->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('portfolioReadiness.checks', function (mixed $checks): bool {
                    $ownerCheck = $this->systemCheck($checks, 'portfolio_owner');

                    return ($ownerCheck['action_label'] ?? null) === 'إعداد المالك';
                }));
    }

    public function test_confirmations_require_evidence_and_stay_in_their_scope(): void
    {
        $portfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->put(route('system-readiness.update'), [
                'key' => 'database_backup',
                'confirmed' => true,
                'evidence' => 'Hostinger backup DB-2026-07-25 verified.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($superadmin)
            ->put(route('system-readiness.update'), [
                'key' => 'legal_terms_ar',
                'confirmed' => true,
                'evidence' => 'Arabic lease wording version 3 approved by counsel.',
                'portfolio_id' => $portfolio->id,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('operational_readiness_checks', [
            'scope_key' => 'system',
            'key' => 'database_backup',
            'portfolio_id' => null,
            'is_confirmed' => true,
            'confirmed_by_user_id' => $superadmin->id,
        ]);
        $this->assertDatabaseHas('operational_readiness_checks', [
            'scope_key' => "portfolio:{$portfolio->id}",
            'key' => 'legal_terms_ar',
            'portfolio_id' => $portfolio->id,
            'is_confirmed' => true,
            'confirmed_by_user_id' => $superadmin->id,
        ]);

        $this->actingAs($superadmin)
            ->put(route('system-readiness.update'), [
                'key' => 'restore_drill',
                'confirmed' => true,
                'evidence' => '',
            ])
            ->assertSessionHasErrors('evidence');

        $this->actingAs($superadmin)
            ->put(route('system-readiness.update'), [
                'key' => 'opening_data',
                'confirmed' => true,
                'evidence' => 'Opening balances reconciled.',
            ])
            ->assertSessionHasErrors('portfolio_id');

        $this->actingAs($superadmin)
            ->put(route('system-readiness.update'), [
                'key' => 'database_backup',
                'confirmed' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('operational_readiness_checks', [
            'scope_key' => 'system',
            'key' => 'database_backup',
            'is_confirmed' => false,
            'confirmed_by_user_id' => null,
            'evidence' => null,
        ]);
        $this->assertGreaterThan(0, OperationalReadinessCheck::query()->count());
    }

    public function test_non_superadmins_cannot_write_readiness_evidence_or_send_mail(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->put(route('system-readiness.update'), [
                'key' => 'database_backup',
                'confirmed' => true,
                'evidence' => 'Should not be accepted.',
            ])
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('system-readiness.test-email'))
            ->assertForbidden();

        $this->assertDatabaseCount('operational_readiness_checks', 0);
    }

    public function test_superadmin_can_send_an_explicit_smtp_test_to_their_own_address(): void
    {
        Notification::fake();
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'noreply@propertycontrol.test',
            'mail.mailers.smtp.host' => 'smtp.propertycontrol.test',
            'mail.mailers.smtp.username' => 'noreply@propertycontrol.test',
        ]);
        $superadmin = $this->createUserWithRole('superadmin', null, [
            'preferred_locale' => 'ar',
        ]);

        $this->actingAs($superadmin)
            ->post(route('system-readiness.test-email'))
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $superadmin,
            ReadinessTestNotification::class,
            fn (ReadinessTestNotification $notification): bool => $notification->locale === 'ar',
        );
    }

    public function test_arabic_workspace_has_rtl_launch_and_evidence_copy(): void
    {
        $portfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->withSession(['locale' => 'ar'])
            ->get(route('system-readiness.index', ['portfolio_id' => $portfolio->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.system_readiness', 'جاهزية الإطلاق')
                ->where('app.translations.readiness.title', 'جاهزية الإطلاق')
                ->where('app.translations.readiness.create_live_portfolio', 'إنشاء محفظة فعلية')
                ->where('systemConfirmations.0.label', 'تم استلام بريد SMTP')
                ->where('portfolioConfirmations.0.label', 'اعتماد الصياغة القانونية الإنجليزية'));
    }

    public function test_scheduler_heartbeat_command_records_runtime_evidence(): void
    {
        Cache::forget(RecordSchedulerHeartbeat::CACHE_KEY);

        $command = $this->artisan('property:record-scheduler-heartbeat');

        $this->assertInstanceOf(PendingCommand::class, $command);
        $command
            ->expectsOutput('Scheduler heartbeat recorded.')
            ->assertSuccessful();
        $this->assertSame(0, $command->run());

        $heartbeat = Cache::get(RecordSchedulerHeartbeat::CACHE_KEY);

        $this->assertIsArray($heartbeat);
        $this->assertSame(2, $heartbeat['version'] ?? null);
        $this->assertCount(1, $heartbeat['samples'] ?? []);
    }

    public function test_one_fresh_heartbeat_cannot_make_cron_look_healthy(): void
    {
        config([
            'operations.scheduler_php_binary' => '/opt/alt/php84/usr/bin/php',
        ]);
        Cache::forget(RecordSchedulerHeartbeat::CACHE_KEY);
        app(RecordSchedulerHeartbeat::class)->handle();
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->get(route('system-readiness.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('systemChecks', function (mixed $checks): bool {
                    $scheduler = $this->systemCheck($checks, 'scheduler');

                    return ($scheduler['status'] ?? null) === 'attention'
                        && ($scheduler['meta']['sample_count'] ?? null) === 1
                        && ($scheduler['meta']['cadence_confirmed'] ?? null) === false
                        && str_contains(
                            (string) ($scheduler['command'] ?? ''),
                            "/opt/alt/php84/usr/bin/php' '"
                                .base_path('artisan')
                                ."' schedule:run",
                        )
                        && str_contains(
                            (string) ($scheduler['detail'] ?? ''),
                            'only 1 recent cadence samples',
                        );
                }));
    }

    public function test_legacy_heartbeat_builds_into_confirmed_cadence_history(): void
    {
        $now = CarbonImmutable::now();
        Cache::forever(
            RecordSchedulerHeartbeat::CACHE_KEY,
            $now->subMinutes(2)->toIso8601String(),
        );

        try {
            foreach ([$now->subMinute(), $now] as $recordedAt) {
                CarbonImmutable::setTestNow($recordedAt);
                app(RecordSchedulerHeartbeat::class)->handle();
            }
        } finally {
            CarbonImmutable::setTestNow();
        }

        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->get(route('system-readiness.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('systemChecks', function (mixed $checks): bool {
                    $scheduler = $this->systemCheck($checks, 'scheduler');

                    return ($scheduler['status'] ?? null) === 'ready'
                        && ($scheduler['meta']['sample_count'] ?? null) === 3
                        && ($scheduler['meta']['cadence_confirmed'] ?? null) === true;
                }));
    }

    public function test_shared_hosting_schedule_locks_expire_quickly_after_a_killed_process(): void
    {
        $events = collect(app(Schedule::class)->events());
        $heartbeat = $events->first(
            fn ($event): bool => str_contains((string) $event->command, 'property:record-scheduler-heartbeat'),
        );
        $queue = $events->first(
            fn ($event): bool => str_contains((string) $event->command, 'queue:work --stop-when-empty'),
        );
        $statusSync = $events->first(
            fn ($event): bool => str_contains((string) $event->command, 'property:sync-operational-statuses'),
        );

        $this->assertNotNull($heartbeat);
        $this->assertNotNull($queue);
        $this->assertNotNull($statusSync);
        $this->assertSame(5, $heartbeat->expiresAt);
        $this->assertSame(10, $queue->expiresAt);
        $this->assertSame(120, $statusSync->expiresAt);
    }

    public function test_queue_readiness_explains_the_age_of_stuck_work(): void
    {
        config(['queue.default' => 'database']);
        $superadmin = $this->createUserWithRole('superadmin');
        $createdAt = now()->subHour()->getTimestamp();

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $createdAt,
            'created_at' => $createdAt,
        ]);

        $this->actingAs($superadmin)
            ->get(route('system-readiness.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('systemChecks', function (mixed $checks): bool {
                    $queue = $this->systemCheck($checks, 'queue');
                    $detail = $queue['detail'] ?? null;

                    return ($queue['status'] ?? null) === 'blocked'
                        && is_string($detail)
                        && str_contains($detail, 'oldest pending job 60 minutes');
                }));
    }

    /** @return array<string, mixed>|null */
    private function systemCheck(mixed $checks, string $key): ?array
    {
        if (! is_iterable($checks)) {
            return null;
        }

        foreach ($checks as $check) {
            if (is_array($check) && ($check['key'] ?? null) === $key) {
                return $check;
            }
        }

        return null;
    }

    private function recordSchedulerCadence(): void
    {
        Cache::forget(RecordSchedulerHeartbeat::CACHE_KEY);
        $now = CarbonImmutable::now();

        try {
            foreach ([$now->subMinutes(2), $now->subMinute(), $now] as $recordedAt) {
                CarbonImmutable::setTestNow($recordedAt);
                app(RecordSchedulerHeartbeat::class)->handle();
            }
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
