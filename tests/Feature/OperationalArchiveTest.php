<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Modules\Payments\Actions\PaymentAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_archive_assets_outside_their_portfolio(): void
    {
        $ownerPortfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $ownerPortfolio);
        $foreignAsset = $this->createAsset($foreignPortfolio);

        $this->actingAs($owner)
            ->delete(route('assets.destroy', $foreignAsset))
            ->assertForbidden();

        $this->assertSame('active', $foreignAsset->fresh()->status);
    }

    public function test_asset_archive_is_blocked_when_descendant_has_active_lease(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $building = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'code' => 'BUILD-ARCHIVE',
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $building->id,
            'code' => 'UNIT-ARCHIVE',
        ]);

        $this->createLease($portfolio, $tenant, $unit, $owner);

        $this->actingAs($owner)
            ->delete(route('assets.destroy', $building))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('active', $building->fresh()->status);
        $this->assertSame('active', $unit->fresh()->status);
    }

    public function test_terminating_lease_marks_asset_vacant(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['occupancy_status' => 'occupied']);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner);

        $this->actingAs($owner)
            ->delete(route('leases.destroy', $lease))
            ->assertRedirect(route('leases.show', $lease));

        $this->assertSame('terminated', $lease->fresh()->status);
        $this->assertSame('vacant', $asset->fresh()->occupancy_status);
    }

    public function test_voiding_payment_reverses_installment_allocations(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner);

        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'VOID-ME',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 1000,
            'currency' => 'SAR',
        ]);

        app(PaymentAllocator::class)->allocate($payment);
        $installment = $lease->installments()->orderBy('due_date')->firstOrFail();

        $this->assertSame(1000.0, (float) $installment->fresh()->amount_paid);
        $this->assertGreaterThan(0, $payment->allocations()->count());

        $this->actingAs($owner)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('payments.show', $payment));

        $this->assertSame('void', $payment->fresh()->status);
        $this->assertSame(0, $payment->allocations()->count());
        $this->assertSame(0.0, (float) $installment->fresh()->amount_paid);
        $this->assertSame('overdue', $installment->fresh()->status);
    }

    public function test_tenant_can_only_cancel_their_own_open_maintenance_request(): void
    {
        $portfolio = $this->createPortfolio();
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $otherUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $otherTenant = $this->createTenantProfile($portfolio, $otherUser);
        $asset = $this->createAsset($portfolio);

        $ownRequest = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'plumbing',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Own request',
            'description' => 'Tenant can cancel this.',
            'requested_at' => now(),
        ]);

        $otherRequest = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $otherTenant->id,
            'submitted_by_user_id' => $otherUser->id,
            'category' => 'plumbing',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Other request',
            'description' => 'Tenant cannot cancel this.',
            'requested_at' => now(),
        ]);

        $this->actingAs($tenantUser)
            ->delete(route('maintenance-requests.destroy', $otherRequest))
            ->assertForbidden();

        $this->actingAs($tenantUser)
            ->delete(route('maintenance-requests.destroy', $ownRequest))
            ->assertRedirect(route('maintenance-requests.show', $ownRequest));

        $this->assertSame('open', $otherRequest->fresh()->status);
        $this->assertSame('cancelled', $ownRequest->fresh()->status);
    }
}
