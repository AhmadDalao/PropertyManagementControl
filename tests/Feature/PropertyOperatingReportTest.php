<?php

namespace Tests\Feature;

use App\Models\AssetStakeholder;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PropertyOperatingReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_opens_a_dedicated_property_report_with_scoped_structure_and_operations(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'North Portfolio',
            'name_ar' => 'محفظة الشمال',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'North Owner']);
        $manager = $this->createUserWithRole('property_manager', $portfolio, ['name' => 'North Manager']);
        $property = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'title_en' => 'North Tower',
            'title_ar' => 'برج الشمال',
            'code' => 'NORTH-01',
            'rentable' => false,
            'valuation_amount' => 5000000,
        ]);
        $floor = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'asset_type' => 'floor',
            'rentable' => false,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $floor->id,
            'asset_type' => 'unit',
            'title_en' => 'North Unit',
            'occupancy_status' => 'occupied',
        ]);
        $vacantUnit = $this->createAsset($portfolio, [
            'parent_id' => $floor->id,
            'asset_type' => 'unit',
            'occupancy_status' => 'vacant',
        ]);
        $otherProperty = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'rentable' => false,
        ]);
        $otherUnit = $this->createAsset($portfolio, [
            'parent_id' => $otherProperty->id,
            'asset_type' => 'unit',
            'occupancy_status' => 'occupied',
        ]);

        foreach ([[$owner, 'owner'], [$manager, 'manager']] as [$user, $relationship]) {
            AssetStakeholder::query()->create([
                'asset_id' => $property->id,
                'portfolio_id' => $portfolio->id,
                'user_id' => $user->id,
                'relationship_type' => $relationship,
                'is_primary' => true,
            ]);
        }

        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'North Tenant']),
        );
        $lease = $this->createLease($portfolio, $tenant, $unit, $manager);
        $otherTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $otherLease = $this->createLease($portfolio, $otherTenant, $otherUnit, $manager);

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => 'NORTH-PAY',
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 1800,
            'currency' => 'SAR',
        ]);
        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $otherLease->id,
            'tenant_profile_id' => $otherTenant->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => 'OTHER-PAY',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 9000,
            'currency' => 'SAR',
        ]);
        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'created_by_user_id' => $manager->id,
            'category' => 'maintenance',
            'title' => 'North repair',
            'incurred_on' => now()->toDateString(),
            'amount' => 300,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);
        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenant->user_id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'North leak',
            'description' => 'Kitchen leak',
            'requested_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('reports.properties.show', [
                'asset' => $property,
                'date_from' => now()->startOfYear()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/property')
                ->where('property.id', $property->id)
                ->where('property.title_en', 'North Tower')
                ->where('property.owner.name', 'North Owner')
                ->where('property.manager.name', 'North Manager')
                ->where('property.structure.records', 4)
                ->where('property.structure.floors', 1)
                ->where('property.structure.units', 2)
                ->where('property.structure.rentable', 2)
                ->where('property.structure.occupied', 1)
                ->where('property.structure.vacant', 1)
                ->where('property.structure.active_tenants', 1)
                ->where('summary.revenue', fn (int|float $value) => (float) $value === 1800.0)
                ->where('summary.expenses', fn (int|float $value) => (float) $value === 300.0)
                ->where('summary.openRequests', 1)
                ->where('recentPayments.0.reference', 'NORTH-PAY')
                ->where('maintenanceBacklog.0.title', 'North leak')
                ->where('property.links.action_center', route('action-center.index', [
                    'property_id' => $property->id,
                ], false))
                ->where('property.downloads.xlsx', fn (string $href): bool => str_contains(
                    $href,
                    'property_id='.$property->id,
                )));
    }

    public function test_property_report_rejects_tenants_children_and_unassigned_properties(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $property = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'rentable' => false,
        ]);
        $child = $this->createAsset($portfolio, ['parent_id' => $property->id]);
        $otherProperty = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'rentable' => false,
        ]);
        $rootFloor = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'floor',
            'rentable' => false,
        ]);
        $this->assignManagerToAsset($manager, $property);

        $this->actingAs($manager)
            ->get(route('reports.properties.show', $property))
            ->assertOk();
        $this->actingAs($manager)
            ->get(route('reports.properties.show', $otherProperty))
            ->assertForbidden();
        $this->actingAs($owner)
            ->get(route('reports.properties.show', $child))
            ->assertNotFound();
        $this->actingAs($owner)
            ->get(route('reports.properties.show', $rootFloor))
            ->assertNotFound();
        $this->actingAs($tenant)
            ->get(route('reports.properties.show', $property))
            ->assertForbidden();
    }
}
