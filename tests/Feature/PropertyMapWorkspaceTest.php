<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PropertyMapWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_open_direct_property_map_with_scoped_clickable_land_records(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $asset = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Owner Map Parcel',
            'code' => 'OWNER-MAP-77',
            'meta_json' => [
                'map' => [
                    'zone' => 'Owner Zone',
                    'land_number' => 'OZ-77',
                    'latitude' => 24.7136,
                    'longitude' => 46.6753,
                ],
            ],
        ]);
        $this->createAsset($foreignPortfolio, [
            'asset_type' => 'building',
            'title_en' => 'Hidden Parcel',
            'code' => 'HIDDEN-MAP-88',
            'meta_json' => [
                'map' => [
                    'zone' => 'Hidden Zone',
                    'land_number' => 'HZ-88',
                    'latitude' => 21.5433,
                    'longitude' => 39.1728,
                ],
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('property-map.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/property-map/index')
                ->where('propertyMap.summary.total', 1)
                ->where('propertyMap.summary.ready', 1)
                ->where('propertyMap.summary.coverage_percent', fn (int|float $value) => (float) $value === 100.0)
                ->where('propertyMap.assets.0.title', 'Owner Map Parcel')
                ->where('propertyMap.assets.0.zone', 'Owner Zone')
                ->where('propertyMap.assets.0.land_number', 'OZ-77')
                ->where('propertyMap.assets.0.href', route('assets.show', $asset))
                ->where('propertyMap.assets.0.edit_href', route('assets.edit', $asset))
                ->where('propertyMap.assets', fn ($assets) => collect($assets)->contains('code', 'OWNER-MAP-77')
                    && ! collect($assets)->contains('code', 'HIDDEN-MAP-88')));
    }

    public function test_superadmin_can_filter_the_direct_property_map_by_portfolio(): void
    {
        $portfolio = $this->createPortfolio();
        $otherPortfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');

        $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'code' => 'FILTERED-IN',
        ]);
        $this->createAsset($otherPortfolio, [
            'asset_type' => 'building',
            'code' => 'FILTERED-OUT',
        ]);

        $this->actingAs($superadmin)
            ->get(route('property-map.index', ['portfolio_id' => $portfolio->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/property-map/index')
                ->where('filters.portfolio_id', $portfolio->id)
                ->where('propertyMap.summary.total', 1)
                ->where('propertyMap.assets', fn ($assets) => collect($assets)->contains('code', 'FILTERED-IN')
                    && ! collect($assets)->contains('code', 'FILTERED-OUT')));
    }

    public function test_property_map_exposes_zone_and_land_directory_records_with_detail_links(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $northAsset = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'North Land 12',
            'code' => 'NORTH-12',
            'meta_json' => [
                'map' => [
                    'zone' => 'North Zone',
                    'land_number' => 'NZ-12',
                    'x' => 42,
                    'y' => 35,
                ],
            ],
        ]);
        $southAsset = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'South Land 3',
            'code' => 'SOUTH-03',
            'meta_json' => [
                'map' => [
                    'zone' => 'South Zone',
                    'land_number' => 'SZ-03',
                    'x' => 58,
                    'y' => 51,
                ],
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('property-map.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/property-map/index')
                ->where('propertyMap.summary.total', 2)
                ->where('propertyMap.summary.zones', ['North Zone', 'South Zone'])
                ->where('propertyMap.assets', fn ($assets) => collect($assets)->contains(fn ($asset) => $asset['zone'] === 'North Zone'
                    && $asset['land_number'] === 'NZ-12'
                    && $asset['href'] === route('assets.show', $northAsset))
                    && collect($assets)->contains(fn ($asset) => $asset['zone'] === 'South Zone'
                        && $asset['land_number'] === 'SZ-03'
                        && $asset['href'] === route('assets.show', $southAsset))));
    }

    public function test_property_map_does_not_fake_missing_zone_or_land_number(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $asset = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Needs Real Map Data',
            'code' => 'NEEDS-MAP-DATA',
        ]);

        $this->actingAs($owner)
            ->get(route('property-map.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/property-map/index')
                ->where('propertyMap.summary.total', 1)
                ->where('propertyMap.summary.ready', 0)
                ->where('propertyMap.summary.needs_position', 1)
                ->where('propertyMap.summary.needs_identity', 1)
                ->where('propertyMap.summary.zones', [])
                ->where('propertyMap.assets.0.code', 'NEEDS-MAP-DATA')
                ->where('propertyMap.assets.0.zone', null)
                ->where('propertyMap.assets.0.land_number', null)
                ->where('propertyMap.assets.0.has_coordinates', false)
                ->where('propertyMap.assets.0.has_identity', false)
                ->where('propertyMap.assets.0.href', route('assets.show', $asset))
                ->where('propertyMap.assets.0.edit_href', route('assets.edit', $asset)));
    }

    public function test_tenant_cannot_open_the_admin_property_map(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        $this->actingAs($tenant)
            ->get(route('property-map.index'))
            ->assertForbidden();
    }
}
