<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPortfolioScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_record_payment_for_a_foreign_portfolio_lease(): void
    {
        $ownerPortfolio = $this->createPortfolio(['code' => 'OWNER-A', 'slug' => 'owner-a']);
        $foreignPortfolio = $this->createPortfolio(['code' => 'OWNER-B', 'slug' => 'owner-b']);

        $owner = $this->createUserWithRole('owner', $ownerPortfolio);
        $foreignManager = $this->createUserWithRole('owner', $foreignPortfolio);
        $tenantUser = $this->createUserWithRole('tenant', $foreignPortfolio);
        $tenantProfile = $this->createTenantProfile($foreignPortfolio, $tenantUser);
        $asset = $this->createAsset($foreignPortfolio);
        $lease = $this->createLease($foreignPortfolio, $tenantProfile, $asset, $foreignManager);

        $this->actingAs($owner)
            ->post(route('payments.store'), [
                'lease_id' => $lease->id,
                'type' => 'rent',
                'method' => 'cash',
                'status' => 'posted',
                'received_on' => now()->toDateString(),
                'amount' => 1000,
                'currency' => 'SAR',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }
}
