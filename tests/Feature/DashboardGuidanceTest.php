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
                ->where('propertyMap.assets', fn ($assets) => collect($assets)->contains('code', 'NORTH-LAND')
                    && ! collect($assets)->contains('code', 'FOREIGN-LAND'))
            );
    }

    public function test_module_registry_lists_core_operational_modules(): void
    {
        $modules = ModuleRegistry::operationalModules();

        $this->assertArrayHasKey('dashboard', $modules);
        $this->assertArrayHasKey('assets', $modules);
        $this->assertArrayHasKey('leases', $modules);
        $this->assertArrayHasKey('cms', $modules);
    }
}
