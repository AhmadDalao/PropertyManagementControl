<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Services\LeaseFinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TenantDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_dashboard_shows_current_lease_and_payment_summary(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Tenant One']);
        $tenantProfile = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Unit 101']);

        $lease = $this->createLease($portfolio, $tenantProfile, $asset, $manager, [
            'started_at' => now()->startOfMonth()->toDateString(),
            'ends_at' => now()->startOfMonth()->addMonths(1)->endOfMonth()->toDateString(),
            'rent_amount' => 2000,
            'deposit_amount' => 1000,
        ]);

        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenantProfile->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => 'PAY-TENANT-1',
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 2500,
            'currency' => 'SAR',
        ]);

        app(LeaseFinancialService::class)->allocatePayment($payment);

        $this->actingAs($tenantUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('mode', 'tenant')
                ->where('stats.leaseCode', $lease->code)
                ->where('stats.amountLeft', 2500.0)
                ->where('tenantPortal.lease.code', $lease->code)
                ->has('tenantPortal.payments', 1));
    }
}
