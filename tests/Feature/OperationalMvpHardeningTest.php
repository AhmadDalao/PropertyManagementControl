<?php

namespace Tests\Feature;

use App\Models\Lease;
use App\Models\Payment;
use App\Services\LeaseFinancialService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class OperationalMvpHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        CarbonImmutable::setTestNow('2026-07-21 12:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_reports_and_dashboard_do_not_count_future_contract_balance_as_arrears(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio, ['occupancy_status' => 'occupied']),
            $owner,
            [
                'started_at' => '2026-07-01',
                'ends_at' => '2026-09-30',
                'rent_amount' => 2000,
                'deposit_amount' => 1000,
            ],
        );

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.arrears', 3000)
                ->where('summary.contractBalance', 7000)
                ->where('summary.leasesInArrears', 1)
                ->where('arrearsLeases.0.id', $lease->id)
                ->where('arrearsLeases.0.arrears_amount', 3000));

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.arrears', 3000)
                ->where('arrearsLeases.0.arrears_amount', 3000));
    }

    public function test_lease_status_changes_keep_asset_occupancy_synchronized(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $asset = $this->createAsset($portfolio, ['occupancy_status' => 'vacant']);

        $this->actingAs($owner)
            ->post(route('leases.store'), [
                'tenant_profile_id' => $tenant->id,
                'asset_id' => $asset->id,
                'status' => 'draft',
                'payment_frequency' => 'monthly',
                'started_at' => '2026-08-01',
                'ends_at' => '2027-07-31',
                'rent_amount' => 2000,
                'deposit_amount' => 0,
                'currency' => 'SAR',
            ])
            ->assertRedirect();

        $lease = Lease::query()->where('leaseable_id', $asset->id)->firstOrFail();
        $this->assertSame('vacant', $asset->fresh()->occupancy_status);

        $this->actingAs($owner)
            ->put(route('leases.update', $lease), [
                'status' => 'active',
                'signed_at' => '2026-07-21',
                'notes' => null,
            ])
            ->assertRedirect(route('leases.show', $lease));
        $this->assertSame('occupied', $asset->fresh()->occupancy_status);

        $this->actingAs($owner)
            ->put(route('leases.update', $lease), [
                'status' => 'expired',
                'signed_at' => '2026-07-21',
                'notes' => null,
            ])
            ->assertRedirect(route('leases.show', $lease));
        $this->assertSame('vacant', $asset->fresh()->occupancy_status);
    }

    public function test_asset_cannot_receive_two_active_leases_and_terminated_leases_stay_closed(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $asset = $this->createAsset($portfolio, ['occupancy_status' => 'occupied']);
        $firstLease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $this->createUserWithRole('tenant', $portfolio)),
            $asset,
            $owner,
        );
        $secondTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );

        $this->actingAs($owner)
            ->from(route('leases.create'))
            ->post(route('leases.store'), [
                'tenant_profile_id' => $secondTenant->id,
                'asset_id' => $asset->id,
                'status' => 'active',
                'payment_frequency' => 'monthly',
                'started_at' => '2026-08-01',
                'ends_at' => '2027-07-31',
                'rent_amount' => 2000,
                'deposit_amount' => 0,
                'currency' => 'SAR',
            ])
            ->assertRedirect(route('leases.create'))
            ->assertSessionHasErrors('asset_id');

        $this->assertSame(1, Lease::query()->where('leaseable_id', $asset->id)->count());

        $this->actingAs($owner)
            ->delete(route('leases.destroy', $firstLease))
            ->assertRedirect(route('leases.index'));

        $this->actingAs($owner)
            ->from(route('leases.edit', $firstLease))
            ->put(route('leases.update', $firstLease), [
                'status' => 'active',
                'signed_at' => null,
                'notes' => null,
            ])
            ->assertRedirect(route('leases.edit', $firstLease))
            ->assertSessionHasErrors('status');
    }

    public function test_ended_leases_and_stale_installments_are_synchronized_by_command(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $asset = $this->createAsset($portfolio, ['occupancy_status' => 'occupied']);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $this->createUserWithRole('tenant', $portfolio)),
            $asset,
            $owner,
            [
                'started_at' => '2026-05-01',
                'ends_at' => '2026-07-20',
                'deposit_amount' => 0,
            ],
        );
        $lease->installments()->update(['status' => 'pending']);

        $this->artisan('property:sync-operational-statuses')
            ->expectsOutputToContain('Expired 1 leases')
            ->assertSuccessful();

        $this->assertSame('expired', $lease->fresh()->status);
        $this->assertSame('vacant', $asset->fresh()->occupancy_status);
        $this->assertSame('overdue', $lease->installments()->oldest('id')->firstOrFail()->status);
    }

    public function test_temporary_password_blocks_portal_until_replaced(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, [
            'force_password_reset' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('assets.index'))
            ->assertRedirect(route('profile.index'))
            ->assertSessionHas('warning');

        $this->actingAs($owner)
            ->get(route('profile.index'))
            ->assertOk();
    }

    public function test_web_responses_include_baseline_security_headers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    }

    public function test_failed_payment_allocation_rolls_back_the_payment_record(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );

        $financials = $this->mock(LeaseFinancialService::class);
        $financials->shouldReceive('allocatePayment')->once()->andThrow(new RuntimeException('Allocation failed'));

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($owner)->post(route('payments.store'), [
                'lease_id' => $lease->id,
                'tenant_profile_id' => $tenant->id,
                'type' => 'rent',
                'method' => 'cash',
                'status' => 'posted',
                'reference' => 'ROLLBACK-PAYMENT',
                'received_on' => '2026-07-21',
                'amount' => 1000,
                'currency' => 'SAR',
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Allocation failed', $exception->getMessage());
        }

        $this->assertDatabaseMissing('payments', ['reference' => 'ROLLBACK-PAYMENT']);
        $this->assertSame(0, Payment::query()->count());
    }
}
