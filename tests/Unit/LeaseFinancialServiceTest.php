<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Services\LeaseFinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaseFinancialServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_deposit_and_monthly_installments(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenantProfile = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        $lease = $this->createLease(
            $portfolio,
            $tenantProfile,
            $asset,
            $manager,
            [
                'started_at' => '2026-01-01',
                'ends_at' => '2026-03-31',
                'rent_amount' => 2000,
                'deposit_amount' => 1500,
            ],
            false
        );

        app(LeaseFinancialService::class)->syncInstallments($lease);

        $installments = $lease->fresh('installments')->installments;

        $this->assertCount(4, $installments);
        $this->assertSame('deposit', $installments[0]->line_type);
        $this->assertSame(1500.0, (float) $installments[0]->amount_due);
        $this->assertSame('rent', $installments[1]->line_type);
        $this->assertSame('2026-03-01', $installments[3]->due_date->toDateString());
    }

    public function test_it_respects_frequency_billing_day_tax_and_discount_when_creating_installments(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenantProfile = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        $quarterlyLease = $this->createLease(
            $portfolio,
            $tenantProfile,
            $asset,
            $manager,
            [
                'started_at' => '2026-01-15',
                'ends_at' => '2026-12-31',
                'payment_frequency' => 'quarterly',
                'rent_amount' => 9000,
                'tax_amount' => 500,
                'discount_amount' => 100,
                'deposit_amount' => 3000,
                'billing_day' => 10,
            ],
            false
        );

        app(LeaseFinancialService::class)->syncInstallments($quarterlyLease);

        $quarterlyInstallments = $quarterlyLease->fresh('installments')->installments->values();

        $this->assertCount(5, $quarterlyInstallments);
        $this->assertSame('deposit', $quarterlyInstallments[0]->line_type);
        $this->assertSame('2026-01-15', $quarterlyInstallments[1]->due_date->toDateString());
        $this->assertSame('2026-04-10', $quarterlyInstallments[2]->due_date->toDateString());
        $this->assertSame('Rent Apr 15-Jul 14 2026', $quarterlyInstallments[2]->label);
        $this->assertSame(9400.0, (float) $quarterlyInstallments[1]->amount_due);

        $yearlyLease = $this->createLease(
            $portfolio,
            $tenantProfile,
            $this->createAsset($portfolio),
            $manager,
            [
                'started_at' => '2026-01-01',
                'ends_at' => '2026-12-31',
                'payment_frequency' => 'yearly',
                'rent_amount' => 100000,
                'deposit_amount' => 0,
                'billing_day' => 5,
            ],
            false
        );

        app(LeaseFinancialService::class)->syncInstallments($yearlyLease);

        $yearlyInstallments = $yearlyLease->fresh('installments')->installments->values();

        $this->assertCount(1, $yearlyInstallments);
        $this->assertSame('Rent Jan-Dec 2026', $yearlyInstallments[0]->label);
        $this->assertSame('2026-01-05', $yearlyInstallments[0]->due_date->toDateString());
        $this->assertSame(100000.0, (float) $yearlyInstallments[0]->amount_due);
    }

    public function test_it_allocates_payments_in_due_order_and_updates_installment_statuses(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenantProfile = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $lease = $this->createLease(
            $portfolio,
            $tenantProfile,
            $asset,
            $manager,
            [
                'started_at' => '2026-01-01',
                'ends_at' => '2026-02-28',
                'rent_amount' => 2000,
                'deposit_amount' => 1000,
            ]
        );

        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenantProfile->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => 'PAY-ALLOC-1',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => '2026-01-01',
            'amount' => 3500,
            'currency' => 'SAR',
        ]);

        app(LeaseFinancialService::class)->allocatePayment($payment);

        $installments = $lease->fresh('installments')->installments->values();

        $this->assertCount(3, $payment->fresh('allocations')->allocations);
        $this->assertSame('paid', $installments[0]->status);
        $this->assertSame(1000.0, (float) $installments[0]->amount_paid);
        $this->assertSame('paid', $installments[1]->status);
        $this->assertSame(2000.0, (float) $installments[1]->amount_paid);
        $this->assertSame('overdue', $installments[2]->status);
        $this->assertSame(500.0, (float) $installments[2]->amount_paid);
    }
}
