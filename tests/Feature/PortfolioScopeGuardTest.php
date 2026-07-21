<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioScopeGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_lease_creation_rejects_tenant_from_another_portfolio(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $foreignPortfolio);
        $foreignTenant = $this->createTenantProfile($foreignPortfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        $this->actingAs($owner)
            ->post(route('leases.store'), [
                'portfolio_id' => $portfolio->id,
                'tenant_profile_id' => $foreignTenant->id,
                'asset_id' => $asset->id,
                'status' => 'active',
                'payment_frequency' => 'monthly',
                'started_at' => now()->toDateString(),
                'ends_at' => now()->addMonth()->toDateString(),
                'rent_amount' => 1000,
            ])
            ->assertStatus(422);
    }

    public function test_payment_creation_rejects_cross_portfolio_lease_reference(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $tenantUser = $this->createUserWithRole('tenant', $foreignPortfolio);
        $tenant = $this->createTenantProfile($foreignPortfolio, $tenantUser);
        $asset = $this->createAsset($foreignPortfolio);
        $lease = $this->createLease($foreignPortfolio, $tenant, $asset, $foreignOwner);

        $this->actingAs($owner)
            ->post(route('payments.store'), [
                'lease_id' => $lease->id,
                'type' => 'rent',
                'method' => 'cash',
                'status' => 'posted',
                'reference' => 'BAD-SCOPE-PAYMENT',
                'received_on' => now()->toDateString(),
                'amount' => 1000,
            ])
            ->assertForbidden();
    }

    public function test_tenant_maintenance_creation_rejects_unrented_asset(): void
    {
        $portfolio = $this->createPortfolio();
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        $this->actingAs($tenantUser)
            ->post(route('maintenance-requests.store'), [
                'asset_id' => $asset->id,
                'category' => 'plumbing',
                'priority' => 'medium',
                'title' => 'Bad request',
                'description' => 'This tenant does not rent the asset.',
            ])
            ->assertStatus(422);
    }
}
