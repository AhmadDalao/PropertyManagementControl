<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortfolioModuleSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_update_portfolio_module_settings(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'Owner Portfolio',
            'name_ar' => 'محفظة المالك',
            'module_settings' => ['payments' => false],
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->get(route('portfolios.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/portfolios/index')
                ->where('canUpdate', true)
                ->where('auth.user.portfolio.module_settings.payments', false)
                ->has('moduleDefinitions', 10)
            );

        $this->actingAs($owner)
            ->put(route('portfolios.update', $portfolio), [
                'name_en' => 'Updated Portfolio',
                'name_ar' => 'محفظة محدثة',
                'contact_email' => 'owner@example.test',
                'contact_phone' => '+966500000010',
                'city' => 'Riyadh',
                'country' => 'Saudi Arabia',
                'address' => 'King Fahd Road',
                'default_currency' => 'SAR',
                'status' => 'active',
                'module_settings' => [
                    'users' => true,
                    'assets' => true,
                    'tenants' => true,
                    'leases' => true,
                    'payments' => false,
                    'maintenance' => true,
                    'expenses' => false,
                    'reports' => true,
                    'documents' => true,
                    'media' => false,
                ],
            ])
            ->assertRedirect(route('portfolios.index'));

        $portfolio->refresh();
        $this->assertSame('Updated Portfolio', $portfolio->name_en);
        $this->assertFalse($portfolio->module_settings['payments']);
        $this->assertFalse($portfolio->module_settings['expenses']);
        $this->assertFalse($portfolio->module_settings['media']);
        $this->assertTrue($portfolio->module_settings['assets']);
    }

    public function test_manager_cannot_update_portfolio_module_settings(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio);

        $this->actingAs($manager)
            ->get(route('portfolios.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/portfolios/index')
                ->where('canUpdate', false)
            );

        $this->actingAs($manager)
            ->put(route('portfolios.update', $portfolio), [
                'name_en' => 'Bad manager update',
                'name_ar' => $portfolio->name_ar,
                'status' => 'active',
                'module_settings' => ['payments' => false],
            ])
            ->assertForbidden();

        $this->assertNotSame('Bad manager update', $portfolio->fresh()->name_en);
    }

    public function test_disabled_module_blocks_route_and_export_for_portfolio_users(): void
    {
        $portfolio = $this->createPortfolio([
            'module_settings' => [
                'assets' => true,
                'payments' => false,
            ],
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->get(route('payments.index'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('exports.resource', ['resource' => 'payments']))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('assets.index'))
            ->assertOk();
    }

    public function test_global_search_hides_disabled_modules(): void
    {
        $portfolio = $this->createPortfolio([
            'module_settings' => [
                'assets' => true,
                'leases' => true,
                'payments' => false,
            ],
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['code' => 'MOD-ASSET']);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner, ['code' => 'MOD-LEASE']);

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'MOD-PAYMENT',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 500,
            'currency' => 'SAR',
        ]);

        $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => 'MOD-PAYMENT']))
            ->assertOk()
            ->assertJsonPath('direct_url', '')
            ->assertJsonMissing(['group' => 'Payments'])
            ->assertJsonMissing(['title' => 'MOD-PAYMENT']);

        $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => 'MOD-ASSET']))
            ->assertOk()
            ->assertJsonPath('direct_url', route('assets.index', ['search' => 'MOD-ASSET']));
    }

    public function test_superadmin_bypasses_portfolio_module_blocks(): void
    {
        $portfolio = $this->createPortfolio([
            'module_settings' => ['payments' => false],
        ]);
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease($portfolio, $tenant, $this->createAsset($portfolio), $owner);

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'SUPER-PAYMENT',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 500,
            'currency' => 'SAR',
        ]);

        $this->actingAs($superadmin)
            ->get(route('payments.index'))
            ->assertOk();

        $this->actingAs($superadmin)
            ->getJson(route('global-search', ['q' => 'SUPER-PAYMENT']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'SUPER-PAYMENT']);
    }
}
