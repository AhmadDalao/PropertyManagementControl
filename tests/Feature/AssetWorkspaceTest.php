<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
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
            'leaseable_type' => (new Asset)->getMorphClass(),
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

    public function test_asset_create_stores_map_and_land_metadata(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->post(route('assets.store'), [
                'asset_type' => 'building',
                'usage_type' => 'mixed',
                'title_en' => 'Map Ready Tower',
                'title_ar' => 'برج جاهز للخريطة',
                'code' => 'MAP-TOWER',
                'status' => 'active',
                'occupancy_status' => 'vacant',
                'valuation_amount' => 1500000,
                'currency' => 'SAR',
                'area' => 800,
                'address' => 'King Fahd Road, Riyadh',
                'map_zone' => 'Zone Beta',
                'land_number' => 'B-120',
                'latitude' => 24.7136,
                'longitude' => 46.6753,
                'map_x' => 38,
                'map_y' => 44,
                'rentable' => false,
            ])
            ->assertRedirect();

        $asset = Asset::query()->where('code', 'MAP-TOWER')->firstOrFail();

        $this->assertSame('Zone Beta', $asset->meta_json['map']['zone']);
        $this->assertSame('B-120', $asset->meta_json['map']['land_number']);
        $this->assertSame(24.7136, $asset->meta_json['map']['latitude']);
        $this->assertSame(46.6753, $asset->meta_json['map']['longitude']);
        $this->assertEquals(38.0, $asset->meta_json['map']['x']);
        $this->assertEquals(44.0, $asset->meta_json['map']['y']);
    }

    public function test_asset_workspace_map_uses_latitude_and_longitude_for_positions(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Alpha Tower',
            'code' => 'ALPHA-MAP',
            'rentable' => false,
            'meta_json' => [
                'map' => [
                    'zone' => 'Alpha Zone',
                    'land_number' => 'A-1',
                    'latitude' => 24.0,
                    'longitude' => 46.0,
                ],
            ],
        ]);
        $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Beta Tower',
            'code' => 'BETA-MAP',
            'rentable' => false,
            'meta_json' => [
                'map' => [
                    'zone' => 'Beta Zone',
                    'land_number' => 'B-2',
                    'latitude' => 25.0,
                    'longitude' => 47.0,
                ],
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('assets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/assets/index')
                ->where('propertyMap.summary.total', 2)
                ->where('propertyMap.summary.mapped', 2)
                ->where('propertyMap.assets.0.code', 'ALPHA-MAP')
                ->where('propertyMap.assets.0.x', fn (int|float $value) => (float) $value === 10.0)
                ->where('propertyMap.assets.0.y', fn (int|float $value) => (float) $value === 90.0)
                ->where('propertyMap.assets.1.code', 'BETA-MAP')
                ->where('propertyMap.assets.1.x', fn (int|float $value) => (float) $value === 90.0)
                ->where('propertyMap.assets.1.y', fn (int|float $value) => (float) $value === 10.0)
            );
    }

    public function test_asset_detail_exposes_clicked_land_spotlight(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Land Owner']);
        $manager = $this->createUserWithRole('property_manager', $portfolio, ['name' => 'Land Manager']);
        $asset = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Riyadh Parcel Tower',
            'code' => 'RPT-77',
            'address' => 'King Fahd Road',
            'occupancy_status' => 'occupied',
            'meta_json' => [
                'map' => [
                    'zone' => 'Riyadh Prime',
                    'land_number' => 'RP-77',
                    'latitude' => 24.7136,
                    'longitude' => 46.6753,
                    'x' => 31,
                    'y' => 36,
                ],
            ],
        ]);
        $asset->stakeholders()->createMany([
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
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-show')
                ->where('detailPage.spotlight.eyebrow', 'Clicked land record')
                ->where('detailPage.spotlight.title', 'RP-77')
                ->where('detailPage.spotlight.subtitle', 'Riyadh Prime')
                ->where('detailPage.spotlight.description', 'King Fahd Road')
                ->where('detailPage.spotlight.status', 'Occupied')
                ->where('detailPage.spotlight.items.2.value', 'Land Owner')
                ->where('detailPage.spotlight.items.3.value', 'Land Manager')
                ->where('detailPage.spotlight.items.4.value', '24.7136, 46.6753')
                ->where('detailPage.spotlight.items.5.value', '31, 36')
                ->where('detailPage.spotlight.actions.0.href', route('property-map.index'))
                ->where('detailPage.spotlight.actions.1.href', route('assets.edit', $asset))
                ->where('detailPage.decisionCards.0.title', 'Map readiness')
                ->where('detailPage.decisionCards.0.value', 'Ready')
                ->where('detailPage.decisionCards.0.detail', 'Riyadh Prime · RP-77 · 24.7136, 46.6753')
                ->where('detailPage.decisionCards.0.href', route('property-map.index'))
                ->where('detailPage.decisionCards.0.tone', 'teal')
                ->where('detailPage.decisionCards.1.title', 'Rental state')
                ->where('detailPage.decisionCards.1.actionLabel', 'Create lease')
            );
    }

    public function test_superadmin_asset_detail_back_to_map_keeps_portfolio_filter(): void
    {
        $portfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $asset = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Filtered Map Parcel',
            'code' => 'FILTERED-PARCEL',
        ]);

        $this->actingAs($superadmin)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-show')
                ->where('detailPage.spotlight.actions.0.href', route('property-map.index', ['portfolio_id' => $portfolio->id]))
            );
    }

    public function test_asset_detail_links_operational_records_for_owner_decisions(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Decision Tenant']);
        $tenantProfile = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Decision Parcel',
            'code' => 'DECISION-PARCEL',
            'currency' => 'SAR',
        ]);
        $lease = $this->createLease($portfolio, $tenantProfile, $asset, $owner);
        $request = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenantProfile->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'electrical',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'Panel issue',
            'description' => 'Breaker panel needs inspection.',
            'requested_at' => now(),
        ]);
        $expense = ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'created_by_user_id' => $owner->id,
            'title' => 'Panel repair',
            'category' => 'electrical',
            'status' => 'posted',
            'amount' => 350,
            'currency' => 'SAR',
            'incurred_on' => now()->toDateString(),
        ]);

        $this->actingAs($owner)
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-show')
                ->where('detailPage.stats.3.value', 1)
                ->where('detailPage.stats.4.value', 1)
                ->where('detailPage.stats.5.value', '350.00 SAR')
                ->where('detailPage.decisionCards.1.title', 'Rental state')
                ->where('detailPage.decisionCards.1.value', 'Active')
                ->where('detailPage.decisionCards.1.href', route('leases.show', $lease))
                ->where('detailPage.decisionCards.2.title', 'Operations risk')
                ->where('detailPage.decisionCards.2.value', 1)
                ->where('detailPage.decisionCards.2.tone', 'danger')
                ->where('detailPage.decisionCards.3.title', 'Financial position')
                ->where('detailPage.decisionCards.3.actionLabel', 'Add expense')
                ->where('detailPage.related.1.title', 'Leases')
                ->where('detailPage.related.1.rows.0.Lease', $lease->code)
                ->where('detailPage.related.1.rows.0.Open.href', route('leases.show', $lease))
                ->where('detailPage.related.1.actionHref', route('leases.create', ['asset_id' => $asset->id]))
                ->where('detailPage.related.2.title', 'Maintenance')
                ->where('detailPage.related.2.rows.0.Request', '#'.$request->id.' Panel issue')
                ->where('detailPage.related.2.rows.0.Open.href', route('maintenance-requests.show', $request))
                ->where('detailPage.related.2.actionHref', route('maintenance-requests.create', ['asset_id' => $asset->id]))
                ->where('detailPage.related.3.title', 'Expenses')
                ->where('detailPage.related.3.rows.0.Expense', 'Panel repair')
                ->where('detailPage.related.3.rows.0.Open.href', route('expenses.show', $expense))
                ->where('detailPage.related.3.actionHref', route('expenses.create', ['asset_id' => $asset->id]))
            );
    }
}
