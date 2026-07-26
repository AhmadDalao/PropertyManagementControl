<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaintenanceWorkOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_manages_a_portfolio_scoped_contractor_directory(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignVendor = MaintenanceVendor::query()->create([
            'portfolio_id' => $foreignPortfolio->id,
            'name' => 'Foreign Plumbing',
            'service_category' => 'plumbing',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->post(route('maintenance-vendors.store'), [
                'name' => 'Reliable Electric',
                'contact_name' => 'Nora',
                'phone' => '+966500000001',
                'email' => 'service@example.test',
                'service_category' => 'electricity',
                'status' => 'active',
                'notes' => 'Same-day emergency coverage.',
            ])
            ->assertRedirect();

        $vendor = MaintenanceVendor::query()
            ->where('portfolio_id', $portfolio->id)
            ->firstOrFail();

        $this->actingAs($owner)
            ->get(route('maintenance-vendors.index', ['search' => 'Reliable']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/maintenance-vendors/index')
                ->where('vendors.total', 1)
                ->where('vendors.data.0.id', $vendor->id)
                ->where('vendors.data.0.name', 'Reliable Electric')
                ->where('vendorInsights.total', 1));

        $this->actingAs($owner)
            ->get(route('maintenance-vendors.show', $foreignVendor))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('maintenance-vendors.destroy', $vendor))
            ->assertRedirect(route('maintenance-vendors.show', $vendor));

        $this->assertDatabaseHas('maintenance_vendors', [
            'id' => $vendor->id,
            'status' => 'inactive',
        ]);
    }

    public function test_owner_schedules_work_and_tenant_sees_only_service_visit_data(): void
    {
        [$portfolio, $owner, $manager, $tenantUser, $request] = $this->fixture();
        $vendor = $this->vendor($portfolio->id);

        $this->travelTo('2026-07-27 10:00:00');

        $this->actingAs($owner)
            ->post(route('maintenance-requests.work-orders.store', $request), [
                'vendor_id' => $vendor->id,
                'assigned_to_user_id' => $manager->id,
                'status' => 'scheduled',
                'scheduled_at' => '2026-07-28 15:30:00',
                'estimated_amount' => '450.00',
                'scope' => 'Inspect the leak and replace the failed valve.',
                'tenant_access_required' => true,
            ])
            ->assertRedirect();

        $workOrder = MaintenanceWorkOrder::query()->firstOrFail();
        $request->refresh();

        $this->assertSame('in_progress', $request->status);
        $this->assertSame($manager->id, $request->assigned_to_user_id);
        $this->assertSame('Reliable Plumbing', $workOrder->vendor_name);
        $this->assertSame(450.0, $workOrder->estimated_amount);
        $this->assertDatabaseHas('maintenance_updates', [
            'maintenance_request_id' => $request->id,
            'status_to' => 'in_progress',
            'is_public_comment' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('maintenance-requests.work-orders.store', $request), [
                'vendor_id' => $vendor->id,
                'assigned_to_user_id' => $manager->id,
                'status' => 'draft',
                'scheduled_at' => '',
                'estimated_amount' => '250.00',
                'scope' => 'This duplicate active job must be rejected.',
                'tenant_access_required' => false,
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('maintenance_work_orders', 1);

        $this->actingAs($owner)
            ->get(route('maintenance-requests.work-orders.create', $request))
            ->assertConflict();

        $this->actingAs($owner)
            ->get(route('maintenance-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.related.0', fn ($panel): bool => ! collect($panel)
                    ->has('actionHref'))
                ->where('detailPage.header.actions', fn ($actions): bool => ! collect($actions)
                    ->pluck('label')
                    ->contains('Create work order')));

        $this->actingAs($owner)
            ->get(route('maintenance-work-orders.show', $workOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-show')
                ->where('detailPage.header.title', $workOrder->reference_code)
                ->where('detailPage.sections.0.items', fn ($items): bool => collect($items)
                    ->contains(fn ($item): bool => $item['label'] === 'Phone'
                        && $item['value'] === '+966500000002')));

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.related.0.title', 'Service visits')
                ->where('detailPage.related.0.rows.0.Scheduled visit', '2026-07-28 15:30:00')
                ->where('detailPage.related.0.rows.0.Access', 'Tenant access required')
                ->where('detailPage.related.0.rows.0', fn ($row): bool => ! collect($row)
                    ->keys()
                    ->contains(fn ($key): bool => in_array($key, [
                        'Contractor',
                        'Quoted amount',
                        'Final amount',
                    ], true))));

        $this->actingAs($tenantUser)
            ->get(route('maintenance-work-orders.show', $workOrder))
            ->assertForbidden();
    }

    public function test_work_order_completion_requires_valid_transition_cost_and_record(): void
    {
        [$portfolio, $owner, $manager, , $request] = $this->fixture();
        $vendor = $this->vendor($portfolio->id);
        $workOrder = MaintenanceWorkOrder::query()->create([
            'portfolio_id' => $portfolio->id,
            'maintenance_request_id' => $request->id,
            'vendor_id' => $vendor->id,
            'created_by_user_id' => $owner->id,
            'assigned_to_user_id' => $manager->id,
            'reference_code' => 'WO-TEST-001',
            'vendor_name' => $vendor->name,
            'vendor_phone' => $vendor->phone,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'estimated_amount' => 500,
            'currency' => 'SAR',
            'scope' => 'Repair the failed valve.',
            'tenant_access_required' => true,
        ]);

        $invalidTransition = $this->actingAs($owner)
            ->put(route('maintenance-work-orders.update', $workOrder), $this->workOrderPayload(
                $vendor->id,
                $manager->id,
                'completed',
            ));
        $invalidTransition
            ->assertStatus(302)
            ->assertSessionHasErrors('status');

        $this->actingAs($owner)
            ->put(route('maintenance-work-orders.update', $workOrder), $this->workOrderPayload(
                $vendor->id,
                $manager->id,
                'in_progress',
            ))
            ->assertRedirect(route('maintenance-work-orders.show', $workOrder));

        $this->actingAs($owner)
            ->put(route('maintenance-work-orders.update', $workOrder), $this->workOrderPayload(
                $vendor->id,
                $manager->id,
                'completed',
            ))
            ->assertSessionHasErrors(['final_amount', 'completion_notes']);

        $payload = $this->workOrderPayload($vendor->id, $manager->id, 'completed');
        $payload['final_amount'] = '525.75';
        $payload['completion_notes'] = 'Valve replaced and pressure tested.';

        $this->actingAs($owner)
            ->put(route('maintenance-work-orders.update', $workOrder), $payload)
            ->assertRedirect(route('maintenance-work-orders.show', $workOrder));

        $workOrder->refresh();
        $this->assertSame('completed', $workOrder->status);
        $this->assertSame(525.75, $workOrder->final_amount);
        $this->assertNotNull($workOrder->completed_at);

        $this->actingAs($owner)
            ->get(route('maintenance-work-orders.show', $workOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.workflow.actions', fn ($actions): bool => collect($actions)
                    ->pluck('label')
                    ->contains('Record final expense')));

        $this->actingAs($owner)
            ->getJson(route('global-search', ['q' => $workOrder->reference_code]))
            ->assertOk()
            ->assertJsonPath(
                'direct_url',
                route('maintenance-work-orders.show', $workOrder),
            );
    }

    public function test_owner_cannot_use_a_contractor_from_another_portfolio(): void
    {
        [$portfolio, $owner, $manager, , $request] = $this->fixture();
        $foreignPortfolio = $this->createPortfolio();
        $foreignVendor = $this->vendor($foreignPortfolio->id, 'Foreign Vendor');

        $this->actingAs($owner)
            ->post(route('maintenance-requests.work-orders.store', $request), [
                'vendor_id' => $foreignVendor->id,
                'assigned_to_user_id' => $manager->id,
                'status' => 'draft',
                'scheduled_at' => '',
                'estimated_amount' => '100',
                'scope' => 'Must be rejected.',
                'tenant_access_required' => false,
            ])
            ->assertSessionHasErrors('vendor_id');

        $this->assertDatabaseCount('maintenance_work_orders', 0);
        $this->assertDatabaseHas('portfolios', ['id' => $portfolio->id]);
    }

    public function test_tenant_does_not_see_internal_draft_work_orders(): void
    {
        [$portfolio, $owner, $manager, $tenantUser, $request] = $this->fixture();
        $vendor = $this->vendor($portfolio->id);
        MaintenanceWorkOrder::query()->create([
            'portfolio_id' => $portfolio->id,
            'maintenance_request_id' => $request->id,
            'vendor_id' => $vendor->id,
            'created_by_user_id' => $owner->id,
            'assigned_to_user_id' => $manager->id,
            'reference_code' => 'WO-INTERNAL-DRAFT',
            'vendor_name' => $vendor->name,
            'status' => 'draft',
            'currency' => 'SAR',
            'scope' => 'Internal planning draft.',
        ]);

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.related.0.rows', [])
                ->where('detailPage.stats', fn ($stats): bool => collect($stats)
                    ->firstWhere('label', 'Work orders')['value'] === 0));
    }

    /** @return array{0:Portfolio,1:User,2:User,3:User,4:MaintenanceRequest} */
    private function fixture(): array
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $this->assignManagerToAsset($manager, $asset);
        $request = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'Water leak',
            'description' => 'Water is leaking below the kitchen sink.',
            'requested_at' => now(),
            'due_at' => now()->addDays(2),
        ]);

        return [$portfolio, $owner, $manager, $tenantUser, $request];
    }

    private function vendor(int $portfolioId, string $name = 'Reliable Plumbing'): MaintenanceVendor
    {
        return MaintenanceVendor::query()->create([
            'portfolio_id' => $portfolioId,
            'name' => $name,
            'contact_name' => 'Service Desk',
            'phone' => '+966500000002',
            'email' => 'dispatch@example.test',
            'service_category' => 'plumbing',
            'status' => 'active',
        ]);
    }

    /** @return array<string, mixed> */
    private function workOrderPayload(int $vendorId, int $managerId, string $status): array
    {
        return [
            'vendor_id' => $vendorId,
            'assigned_to_user_id' => $managerId,
            'status' => $status,
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'estimated_amount' => '500.00',
            'final_amount' => '',
            'scope' => 'Repair the failed valve.',
            'completion_notes' => '',
            'tenant_access_required' => true,
        ];
    }
}
