<?php

namespace Tests\Feature;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaintenanceServiceWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_manager_queue_exposes_due_dates_expenses_and_full_timeline(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio, ['name' => 'Service Manager']);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Maintenance Tenant']);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Service Unit']);

        $requestItem = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'assigned_to_user_id' => $manager->id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'in_progress',
            'title' => 'Kitchen leak',
            'description' => 'Water below sink.',
            'internal_notes' => 'Call vendor before visiting.',
            'requested_at' => now()->subDay(),
            'due_at' => now()->addDay(),
        ]);

        $requestItem->updates()->create([
            'user_id' => $tenantUser->id,
            'status_to' => 'open',
            'is_public_comment' => true,
            'comment' => 'Tenant opened request.',
            'created_at' => now()->subHours(3),
        ]);
        $requestItem->updates()->create([
            'user_id' => $owner->id,
            'status_from' => 'open',
            'status_to' => 'in_progress',
            'is_public_comment' => false,
            'comment' => 'Internal vendor assigned.',
            'created_at' => now()->subHour(),
        ]);

        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'maintenance_request_id' => $requestItem->id,
            'created_by_user_id' => $owner->id,
            'category' => 'plumbing',
            'title' => 'Leak parts',
            'incurred_on' => now()->toDateString(),
            'amount' => 350,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);

        $this->actingAs($owner)
            ->get(route('maintenance-requests.index', ['search' => 'Kitchen leak']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/maintenance/index')
                ->where('mode', 'manager')
                ->where('requests.total', 1)
                ->where('requests.data.0.title', 'Kitchen leak')
                ->where('requests.data.0.assigned_to.name', 'Service Manager')
                ->where('requests.data.0.expense_total', 350)
                ->where('requests.data.0.internal_notes', 'Call vendor before visiting.')
                ->has('requests.data.0.updates', 2));
    }

    public function test_tenant_queue_only_exposes_public_timeline_and_allows_public_comment(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $this->createLease($portfolio, $tenant, $asset, $owner);

        $requestItem = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'electricity',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Breaker issue',
            'description' => 'Breaker keeps tripping.',
            'internal_notes' => 'Do not show tenant.',
            'requested_at' => now(),
            'due_at' => now()->addDays(4),
        ]);

        $requestItem->updates()->create([
            'user_id' => $owner->id,
            'status_to' => 'open',
            'is_public_comment' => false,
            'comment' => 'Internal note.',
        ]);
        $requestItem->updates()->create([
            'user_id' => $owner->id,
            'status_to' => 'open',
            'is_public_comment' => true,
            'comment' => 'We will visit tomorrow.',
        ]);

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.index', ['search' => 'Breaker']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/maintenance/index')
                ->where('mode', 'tenant')
                ->where('requests.total', 1)
                ->where('requests.data.0.internal_notes', null)
                ->has('requests.data.0.updates', 1)
                ->where('requests.data.0.updates.0.comment', 'We will visit tomorrow.'));

        $this->actingAs($tenantUser)
            ->put(route('maintenance-requests.update', $requestItem), [
                'comment' => 'The breaker panel is accessible after 5 PM.',
            ])
            ->assertRedirect(route('maintenance-requests.index'));

        $this->assertDatabaseHas('maintenance_updates', [
            'maintenance_request_id' => $requestItem->id,
            'user_id' => $tenantUser->id,
            'is_public_comment' => true,
            'comment' => 'The breaker panel is accessible after 5 PM.',
        ]);
    }

    public function test_manager_can_share_a_public_status_update_with_tenant(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        $requestItem = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'ac',
            'priority' => 'urgent',
            'status' => 'open',
            'title' => 'AC stopped',
            'description' => 'No cooling.',
            'requested_at' => now(),
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)
            ->put(route('maintenance-requests.update', $requestItem), [
                'assigned_to_user_id' => $owner->id,
                'priority' => 'urgent',
                'status' => 'in_progress',
                'internal_notes' => 'Vendor booked.',
                'comment' => 'Technician is scheduled for today.',
                'is_public_comment' => true,
            ])
            ->assertRedirect(route('maintenance-requests.index'));

        $requestItem->refresh();
        $this->assertSame('in_progress', $requestItem->status);
        $this->assertNotNull($requestItem->due_at);
        $this->assertDatabaseHas('maintenance_updates', [
            'maintenance_request_id' => $requestItem->id,
            'is_public_comment' => true,
            'comment' => 'Technician is scheduled for today.',
        ]);

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.index', ['search' => 'AC stopped']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('requests.data.0.updates.0.comment', 'Technician is scheduled for today.'));
    }
}
