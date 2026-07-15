<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
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
            ->assertRedirect(route('portfolios.show', $portfolio));

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

    public function test_superadmin_portfolio_workspace_exposes_operational_insights(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'Insight Account',
            'name_ar' => 'حساب المؤشرات',
            'code' => 'INSIGHT-A',
        ]);
        $archivedPortfolio = $this->createPortfolio([
            'name_en' => 'Archived Account',
            'status' => 'archived',
            'code' => 'INSIGHT-B',
        ]);
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, [
            'title_en' => 'Valued Unit',
            'valuation_amount' => 750000,
        ]);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner);

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'PORT-REV',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 4200,
            'currency' => 'SAR',
        ]);

        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Portfolio service backlog',
            'description' => 'Needs attention.',
            'requested_at' => now(),
        ]);

        $this->createAsset($archivedPortfolio, ['valuation_amount' => 100000]);

        $this->actingAs($superadmin)
            ->get(route('portfolios.index', ['search' => 'Insight Account']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/portfolios/index')
                ->where('portfolioInsights.total', 2)
                ->where('portfolioInsights.active', 1)
                ->where('portfolioInsights.archived', 1)
                ->where('portfolioInsights.assets', 2)
                ->where('portfolioInsights.users', 2)
                ->where('portfolioInsights.leases', 1)
                ->where('portfolioInsights.active_leases', 1)
                ->where('portfolioInsights.open_maintenance', 1)
                ->where('portfolioInsights.valuation_total', 850000)
                ->where('portfolioInsights.posted_revenue_total', 4200)
                ->where('portfolios.total', 1)
                ->where('portfolios.data.0.code', 'INSIGHT-A')
                ->where('portfolios.data.0.active_leases_count', 1)
                ->where('portfolios.data.0.open_maintenance_count', 1)
                ->has('statusOptions', 3)
            );
    }

    public function test_owner_portfolio_insights_do_not_leak_other_accounts(): void
    {
        $portfolio = $this->createPortfolio(['code' => 'OWNER-PORT']);
        $foreignPortfolio = $this->createPortfolio(['code' => 'FOREIGN-PORT']);
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->createAsset($portfolio, ['valuation_amount' => 300000]);
        $this->createAsset($foreignPortfolio, ['valuation_amount' => 900000]);

        $this->actingAs($owner)
            ->get(route('portfolios.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/portfolios/index')
                ->where('portfolioInsights.total', 1)
                ->where('portfolioInsights.assets', 1)
                ->where('portfolioInsights.valuation_total', 300000)
                ->where('portfolios.total', 1)
                ->where('portfolios.data.0.code', 'OWNER-PORT')
            );
    }

    public function test_portfolio_status_must_use_known_status_option(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->put(route('portfolios.update', $portfolio), [
                'name_en' => $portfolio->name_en,
                'name_ar' => $portfolio->name_ar,
                'status' => 'random-status',
            ])
            ->assertSessionHasErrors('status');
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
            ->assertJsonPath('direct_url', route('assets.show', $asset));
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
