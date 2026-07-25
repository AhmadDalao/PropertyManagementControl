<?php

namespace Tests\Feature;

use App\Models\AssetStakeholder;
use App\Models\OperationalReadinessCheck;
use App\Modules\SystemReadiness\Actions\RecordSchedulerHeartbeat;
use App\Modules\SystemReadiness\Notifications\ReadinessTestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
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

        app(RecordSchedulerHeartbeat::class)->handle();

        $this->actingAs($superadmin)
            ->get(route('system-readiness.index', ['portfolio_id' => $portfolio->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/system-readiness/index')
                ->has('systemChecks', 7)
                ->has('systemConfirmations', 4)
                ->has('portfolioConfirmations', 6)
                ->where('summary.total', 25)
                ->where('portfolioReadiness.portfolio.id', $portfolio->id)
                ->where('portfolioReadiness.metrics.owners', 1)
                ->where('portfolioReadiness.metrics.managers', 1)
                ->where('portfolioReadiness.metrics.tenants', 1)
                ->where('portfolioReadiness.metrics.properties', 1)
                ->where('portfolioReadiness.metrics.assignment_gaps', 0)
                ->where('systemChecks', fn ($checks): bool => collect($checks)
                    ->firstWhere('key', 'scheduler')['status'] === 'ready'));

        $this->actingAs($owner)
            ->get(route('system-readiness.index'))
            ->assertForbidden();

        $this->actingAs($tenant)
            ->get(route('system-readiness.index'))
            ->assertForbidden();
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
                ->where('systemConfirmations.0.label', 'تم استلام بريد SMTP')
                ->where('portfolioConfirmations.0.label', 'اعتماد الصياغة القانونية الإنجليزية'));
    }

    public function test_scheduler_heartbeat_command_records_runtime_evidence(): void
    {
        Cache::forget(RecordSchedulerHeartbeat::CACHE_KEY);

        $this->artisan('property:record-scheduler-heartbeat')
            ->expectsOutput('Scheduler heartbeat recorded.')
            ->assertSuccessful();

        $this->assertIsString(Cache::get(RecordSchedulerHeartbeat::CACHE_KEY));
    }
}
