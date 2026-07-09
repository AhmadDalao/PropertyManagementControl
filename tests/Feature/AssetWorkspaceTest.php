<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Lease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssetWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_asset_workspace_exposes_insights_hierarchy_and_assignments(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Owner One']);
        $manager = $this->createUserWithRole('property_manager', $portfolio, ['name' => 'Manager One']);
        $building = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'North Tower',
            'code' => 'NORTH-TOWER',
            'rentable' => false,
            'valuation_amount' => 1_000_000,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $building->id,
            'asset_type' => 'unit',
            'title_en' => 'Unit 301',
            'code' => 'NORTH-301',
            'occupancy_status' => 'vacant',
            'rentable' => true,
            'valuation_amount' => 300_000,
        ]);

        $unit->stakeholders()->createMany([
            [
                'portfolio_id' => $portfolio->id,
                'user_id' => $owner->id,
                'relationship_type' => 'owner',
                'is_primary' => true,
            ],
            [
                'portfolio_id' => $portfolio->id,
                'user_id' => $manager->id,
                'relationship_type' => 'manager',
                'is_primary' => true,
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('assets.index', ['search' => 'NORTH-301']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/assets/index')
                ->where('assets.total', 1)
                ->where('assets.data.0.code', 'NORTH-301')
                ->where('assets.data.0.parent.title_en', 'North Tower')
                ->where('assets.data.0.stakeholders.0.user.name', 'Owner One')
                ->where('assets.data.0.stakeholders.1.user.name', 'Manager One')
                ->where('insights.total_assets', 2)
                ->where('insights.rentable_assets', 1)
                ->where('insights.vacant_rentable_assets', 1)
                ->where('insights.total_value', fn (int|float $value) => (float) $value === 1_300_000.0)
                ->where('parentOptions.0.code', 'NORTH-TOWER')
                ->where('parentOptions.0.asset_type', 'building')
                ->where('userOptions.0.name', 'Manager One'));
    }

    public function test_asset_archive_blocks_active_leases_stored_with_morph_alias(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['code' => 'ALIAS-ASSET']);

        Lease::query()->create([
            'portfolio_id' => $portfolio->id,
            'tenant_profile_id' => $tenant->id,
            'managed_by_user_id' => $owner->id,
            'leaseable_type' => (new Asset())->getMorphClass(),
            'leaseable_id' => $asset->id,
            'code' => 'ALIAS-LEASE',
            'status' => 'active',
            'payment_frequency' => 'monthly',
            'started_at' => now()->startOfMonth()->toDateString(),
            'ends_at' => now()->startOfMonth()->addMonth()->toDateString(),
            'rent_amount' => 2500,
            'deposit_amount' => 2500,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'currency' => 'SAR',
        ]);

        $this->actingAs($owner)
            ->delete(route('assets.destroy', $asset))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('active', $asset->fresh()->status);
    }
}
