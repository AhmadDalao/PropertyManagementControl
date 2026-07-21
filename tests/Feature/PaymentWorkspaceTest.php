<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Modules\Payments\Actions\PaymentAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_payment_workspace_exposes_finance_insights_allocations_and_receipts(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Ledger Tenant']);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Ledger Unit']);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner, [
            'rent_amount' => 2000,
            'deposit_amount' => 1000,
        ]);

        $postedPayment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'POSTED-LEDGER',
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 2500,
            'currency' => 'SAR',
        ]);
        app(PaymentAllocator::class)->allocate($postedPayment);

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'PENDING-LEDGER',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'pending',
            'received_on' => now()->toDateString(),
            'amount' => 700,
            'currency' => 'SAR',
        ]);

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'VOID-LEDGER',
            'type' => 'fee',
            'method' => 'card',
            'status' => 'void',
            'received_on' => now()->toDateString(),
            'amount' => 300,
            'currency' => 'SAR',
        ]);

        $this->actingAs($owner)
            ->get(route('payments.index', ['search' => 'POSTED-LEDGER']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/payments/index')
                ->where('payments.total', 1)
                ->where('payments.data.0.reference', 'POSTED-LEDGER')
                ->where('payments.data.0.allocated_amount', 2500)
                ->where('payments.data.0.unallocated_amount', 0)
                ->where('payments.data.0.allocation_count', 2)
                ->where('payments.data.0.receipt_url', route('payments.receipt', $postedPayment))
                ->where('paymentInsights.posted_amount', 2500)
                ->where('paymentInsights.pending_amount', 700)
                ->where('paymentInsights.void_amount', 300)
                ->where('paymentInsights.allocated_amount', 2500)
                ->missing('payments.data.0.allocations')
                ->missing('leaseOptions')
                ->missing('tenantOptions'));
    }

    public function test_pending_payments_do_not_allocate_until_posted_and_can_return_to_pending(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner);
        $firstInstallment = $lease->installments()->orderBy('sequence')->firstOrFail();

        $response = $this->actingAs($owner)
            ->post(route('payments.store'), [
                'lease_id' => $lease->id,
                'type' => 'rent',
                'method' => 'cash',
                'status' => 'pending',
                'reference' => 'PENDING-NO-ALLOC',
                'received_on' => now()->toDateString(),
                'amount' => 1000,
            ]);

        $payment = Payment::query()->where('reference', 'PENDING-NO-ALLOC')->firstOrFail();

        $response->assertRedirect(route('payments.show', $payment));

        $this->assertSame(0, $payment->allocations()->count());
        $this->assertSame(0.0, (float) $firstInstallment->fresh()->amount_paid);

        $this->actingAs($owner)
            ->put(route('payments.update', $payment), [
                'status' => 'posted',
                'notes' => 'Money confirmed.',
            ])
            ->assertRedirect(route('payments.show', $payment));

        $this->assertSame('posted', $payment->fresh()->status);
        $this->assertSame(1, $payment->allocations()->count());
        $this->assertSame(1000.0, (float) $firstInstallment->fresh()->amount_paid);

        $this->actingAs($owner)
            ->put(route('payments.update', $payment), [
                'status' => 'pending',
                'notes' => 'Bank confirmation was wrong.',
            ])
            ->assertRedirect(route('payments.show', $payment));

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(0, $payment->allocations()->count());
        $this->assertSame(0.0, (float) $firstInstallment->fresh()->amount_paid);
    }
}
