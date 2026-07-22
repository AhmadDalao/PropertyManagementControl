<?php

namespace Tests\Feature;

use App\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardGuidanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_dashboard_shows_setup_next_actions(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('mode', 'portfolio')
                ->where('nextActions', fn ($actions) => collect($actions)->contains('label', 'Create users')
                    && collect($actions)->contains('label', 'Create assets')
                    && collect($actions)->contains('label', 'Create profiles')
                    && collect($actions)->contains('href', '/users/create')
                    && collect($actions)->contains('href', '/assets/create')
                    && collect($actions)->contains('href', '/tenants/create')
                    && ! collect($actions)->contains('label', 'Create portfolio'))
            );
    }

    public function test_owner_dashboard_removes_setup_actions_after_cycle_exists(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenantProfile = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        $this->createLease($portfolio, $tenantProfile, $asset, $owner);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('mode', 'portfolio')
                ->where('nextActions', fn ($actions) => ! collect($actions)->contains('label', 'Create assets')
                    && ! collect($actions)->contains('label', 'Create profiles')
                    && ! collect($actions)->contains('label', 'Create leases'))
            );
    }

    public function test_owner_dashboard_exposes_scoped_property_map_assets(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $mappedAsset = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'North District Land',
            'code' => 'NORTH-LAND',
            'rentable' => false,
            'meta_json' => [
                'map' => [
                    'zone' => 'North Zone',
                    'land_number' => 'NZ-44',
                    'latitude' => 24.7136,
                    'longitude' => 46.6753,
                    'x' => 42,
                    'y' => 36,
                ],
            ],
        ]);
        $this->createAsset($foreignPortfolio, [
            'asset_type' => 'building',
            'title_en' => 'Foreign District Land',
            'code' => 'FOREIGN-LAND',
            'meta_json' => [
                'map' => [
                    'zone' => 'Foreign Zone',
                    'land_number' => 'FZ-99',
                ],
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('mode', 'portfolio')
                ->where('propertyMap.summary.total', 1)
                ->where('propertyMap.assets.0.title', 'North District Land')
                ->where('propertyMap.assets.0.zone', 'North Zone')
                ->where('propertyMap.assets.0.land_number', 'NZ-44')
                ->where('propertyMap.assets.0.href', route('assets.show', $mappedAsset))
                ->where('propertyMap.assets.0.edit_href', route('assets.edit', $mappedAsset))
                ->where('propertyMap.assets.0.has_identity', true)
                ->where('propertyMap.summary.ready', 1)
                ->where('propertyMap.summary.needs_position', 0)
                ->where('propertyMap.summary.needs_identity', 0)
                ->where('propertyMap.summary.coverage_percent', fn (int|float $value) => (float) $value === 100.0)
                ->where('propertyMap.assets', fn ($assets) => collect($assets)->contains('code', 'NORTH-LAND')
                    && ! collect($assets)->contains('code', 'FOREIGN-LAND'))
            );
    }

    public function test_owner_dashboard_map_includes_every_scoped_property_without_capping(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        foreach (range(1, 24) as $index) {
            $this->createAsset($portfolio, [
                'asset_type' => 'building',
                'title_en' => sprintf('Mapped Building %02d', $index),
                'code' => sprintf('MAP-BLDG-%02d', $index),
                'rentable' => false,
            ]);
        }

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('propertyMap.summary.total', 24)
                ->has('propertyMap.assets', 24)
            );
    }

    public function test_owner_dashboard_surfaces_incomplete_property_map_as_next_action(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $asset = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Unmapped Owner Building',
            'code' => 'UNMAPPED-OWNER',
            'rentable' => false,
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('nextActions', fn ($actions) => collect($actions)->contains(fn ($action) => $action['label'] === 'Complete property map'
                    && $action['href'] === '/property-map'
                    && str_contains($action['description'], 'missing positions')
                    && str_contains($action['description'], 'missing zone/land labels')))
                ->where('propertyMap.summary.coverage_percent', fn (int|float $value) => (float) $value === 0.0)
                ->where('propertyMap.assets.0.code', 'UNMAPPED-OWNER')
                ->where('propertyMap.assets.0.href', route('assets.show', $asset))
                ->where('propertyMap.assets.0.edit_href', route('assets.edit', $asset))
            );
    }

    public function test_owner_dashboard_map_includes_child_assets_with_explicit_geographic_position(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $building = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Main Parcel',
            'code' => 'MAIN-PARCEL',
        ]);
        $mappedUnit = $this->createAsset($portfolio, [
            'parent_id' => $building->id,
            'asset_type' => 'unit',
            'title_en' => 'Retail Parcel 12',
            'code' => 'RETAIL-12',
            'meta_json' => [
                'map' => [
                    'zone' => 'Retail Strip',
                    'land_number' => 'RS-12',
                    'latitude' => 24.7162,
                    'longitude' => 46.6791,
                    'x' => 52,
                    'y' => 44,
                ],
            ],
        ]);
        $this->createAsset($portfolio, [
            'parent_id' => $building->id,
            'asset_type' => 'unit',
            'title_en' => 'Back Office 2',
            'code' => 'OFFICE-2',
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('propertyMap.summary.total', 2)
                ->where('propertyMap.summary.ready', 1)
                ->where('propertyMap.summary.needs_position', 1)
                ->where('propertyMap.summary.needs_identity', 1)
                ->where('propertyMap.summary.coverage_percent', fn (int|float $value) => (float) $value === 50.0)
                ->where('propertyMap.assets', fn ($assets) => collect($assets)->contains('code', 'MAIN-PARCEL')
                    && collect($assets)->contains('code', 'RETAIL-12')
                    && ! collect($assets)->contains('code', 'OFFICE-2'))
                ->where('propertyMap.assets.1.href', route('assets.show', $mappedUnit))
            );
    }

    public function test_owner_dashboard_map_omits_unpositioned_child_units(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        foreach (range(1, 22) as $index) {
            $this->createAsset($portfolio, [
                'asset_type' => 'unit',
                'title_en' => sprintf('Fallback Unit %02d', $index),
                'code' => sprintf('FALLBACK-UNIT-%02d', $index),
            ]);
        }

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('propertyMap.summary.total', 0)
                ->where('propertyMap.summary.ready', 0)
                ->where('propertyMap.summary.needs_position', 0)
                ->where('propertyMap.summary.needs_identity', 0)
                ->where('propertyMap.summary.coverage_percent', fn (int|float $value) => (float) $value === 0.0)
                ->where('propertyMap.summary.zones', [])
                ->where('propertyMap.summary.payload_limit', 40)
                ->has('propertyMap.assets', 0)
            );
    }

    public function test_module_registry_lists_core_operational_modules(): void
    {
        $modules = ModuleRegistry::operationalModules();

        $this->assertArrayHasKey('dashboard', $modules);
        $this->assertArrayHasKey('assets', $modules);
        $this->assertArrayHasKey('leases', $modules);
        $this->assertArrayHasKey('cms', $modules);
        $this->assertArrayHasKey('public_site', $modules);
    }
}
