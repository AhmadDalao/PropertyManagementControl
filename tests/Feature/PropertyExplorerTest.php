<?php

namespace Tests\Feature;

use App\Models\LeaseInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PropertyExplorerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_move_through_the_hierarchy_and_see_live_tenancy_financials(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Tower Owner']);
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Rose Tower',
            'title_ar' => 'برج روز',
            'code' => 'ROSE',
            'rentable' => false,
        ]);
        $floor = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'asset_type' => 'floor',
            'title_en' => 'First Floor',
            'title_ar' => 'الطابق الأول',
            'code' => 'ROSE-F01',
            'rentable' => false,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $floor->id,
            'asset_type' => 'unit',
            'title_en' => 'Unit 101',
            'title_ar' => 'الوحدة 101',
            'code' => 'ROSE-101',
            'occupancy_status' => 'occupied',
        ]);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Nora Tenant']),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $unit,
            $owner,
            ['code' => 'ROSE-LEASE-101'],
            syncInstallments: false,
        );
        LeaseInstallment::query()->create([
            'lease_id' => $lease->id,
            'sequence' => 1,
            'line_type' => 'rent',
            'label' => 'Current rent',
            'due_date' => today()->subDay(),
            'amount_due' => 1000,
            'amount_paid' => 400,
            'status' => 'partial',
        ]);

        $this->actingAs($owner)
            ->get(route('property-explorer.index', [
                'property_id' => $property->id,
                'node_id' => $floor->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/assets/explorer')
                ->where('explorer.selected.id', $floor->id)
                ->where('explorer.selected.parent.id', $property->id)
                ->has('explorer.breadcrumbs', 2)
                ->where('explorer.breadcrumbs.0.id', $property->id)
                ->where('explorer.breadcrumbs.1.id', $floor->id)
                ->where('explorer.metrics.units', 1)
                ->where('explorer.metrics.occupied', 1)
                ->where('explorer.metrics.active_leases', 1)
                ->where('explorer.metrics.tenants', 1)
                ->where('explorer.metrics.arrears', fn (int|float $value): bool => (float) $value === 600.0)
                ->where('explorer.records.total', 1)
                ->where('explorer.records.data.0.id', $unit->id)
                ->where('explorer.records.data.0.lease.tenant_name', 'Nora Tenant')
                ->where('explorer.records.data.0.lease.code', 'ROSE-LEASE-101'));

        $this->actingAs($owner)
            ->get(route('property-explorer.index', [
                'property_id' => $property->id,
                'node_id' => $unit->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('explorer.selected.id', $unit->id)
                ->where('explorer.active_lease.tenant_name', 'Nora Tenant')
                ->where('explorer.active_lease.total_due', fn (int|float $value): bool => (float) $value === 1000.0)
                ->where('explorer.active_lease.total_paid', fn (int|float $value): bool => (float) $value === 400.0)
                ->where('explorer.active_lease.balance_remaining', fn (int|float $value): bool => (float) $value === 600.0)
                ->where('explorer.active_lease.arrears', fn (int|float $value): bool => (float) $value === 600.0)
                ->where('explorer.records.total', 0));

        $this->actingAs($owner)
            ->get(route('property-explorer.index', [
                'property_id' => $property->id,
                'search' => 'Nora Tenant',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('explorer.records.total', 1)
                ->where('explorer.records.data.0.id', $unit->id)
                ->where('explorer.records.data.0.lease.tenant_name', 'Nora Tenant'));
    }

    public function test_search_filters_the_selected_branch_and_keeps_pagination_bounded(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'code' => 'SEARCH-ROOT',
            'rentable' => false,
        ]);
        $floor = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'asset_type' => 'floor',
            'code' => 'SEARCH-FLOOR',
            'rentable' => false,
        ]);

        foreach (range(1, 13) as $number) {
            $this->createAsset($portfolio, [
                'parent_id' => $floor->id,
                'asset_type' => 'unit',
                'title_en' => "Search Unit {$number}",
                'code' => sprintf('SEARCH-U%02d', $number),
                'occupancy_status' => $number === 13 ? 'maintenance' : 'vacant',
            ]);
        }

        $this->actingAs($owner)
            ->get(route('property-explorer.index', [
                'property_id' => $property->id,
                'node_id' => $floor->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('explorer.records.total', 13)
                ->where('explorer.records.per_page', 12)
                ->has('explorer.records.data', 12));

        $this->actingAs($owner)
            ->get(route('property-explorer.index', [
                'property_id' => $property->id,
                'search' => 'SEARCH-U13',
                'occupancy_status' => 'maintenance',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('explorer.filters.search', 'SEARCH-U13')
                ->where('explorer.records.total', 1)
                ->where('explorer.records.data.0.code', 'SEARCH-U13'));
    }

    public function test_manager_assignment_scope_and_tenant_access_are_enforced(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $visible = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'code' => 'VISIBLE-ROOT',
            'rentable' => false,
        ]);
        $hidden = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'code' => 'HIDDEN-ROOT',
            'rentable' => false,
        ]);
        $this->assignManagerToAsset($manager, $visible);

        $this->actingAs($manager)
            ->get(route('property-explorer.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('explorer.properties', 1)
                ->where('explorer.properties.0.id', $visible->id)
                ->where('explorer.selected.id', $visible->id));

        $this->actingAs($manager)
            ->get(route('property-explorer.index', ['property_id' => $hidden->id]))
            ->assertForbidden();

        $this->actingAs($tenant)
            ->get(route('property-explorer.index'))
            ->assertForbidden();
    }

    public function test_arabic_explorer_keeps_localized_titles_and_rtl_shared_state(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Arabic Ready Tower',
            'title_ar' => 'برج عربي',
            'code' => 'AR-TOWER',
            'rentable' => false,
        ]);

        $this->actingAs($owner)
            ->get(route('property-explorer.index', ['locale' => 'ar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('explorer.selected.id', $property->id)
                ->where('explorer.selected.title_ar', 'برج عربي')
                ->where('app.translations.assets.explorer.title', 'مستكشف العقارات'));
    }
}
