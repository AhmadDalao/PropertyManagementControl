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

    public function test_module_registry_lists_core_operational_modules(): void
    {
        $modules = ModuleRegistry::operationalModules();

        $this->assertArrayHasKey('dashboard', $modules);
        $this->assertArrayHasKey('assets', $modules);
        $this->assertArrayHasKey('leases', $modules);
        $this->assertArrayHasKey('cms', $modules);
    }
}
