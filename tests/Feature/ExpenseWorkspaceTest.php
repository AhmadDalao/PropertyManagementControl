<?php

namespace Tests\Feature;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_expense_workspace_exposes_spend_insights_and_linked_rows(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Kitchen Unit', 'code' => 'KITCHEN-101']);
        $maintenanceRequest = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'Kitchen leak',
            'description' => 'Sink leak',
            'requested_at' => now(),
        ]);

        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'maintenance_request_id' => $maintenanceRequest->id,
            'created_by_user_id' => $owner->id,
            'category' => 'maintenance',
            'title' => 'Kitchen pipe repair',
            'description' => 'Replaced pipe and seal.',
            'incurred_on' => now()->toDateString(),
            'amount' => 450,
            'currency' => 'SAR',
            'vendor_name' => 'Pipe Team',
            'status' => 'posted',
        ]);
        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'created_by_user_id' => $owner->id,
            'category' => 'utilities',
            'title' => 'Electric bill pending',
            'incurred_on' => now()->toDateString(),
            'amount' => 300,
            'currency' => 'SAR',
            'vendor_name' => 'Utility Co',
            'status' => 'pending',
        ]);
        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'created_by_user_id' => $owner->id,
            'category' => 'repairs',
            'title' => 'Voided repair',
            'incurred_on' => now()->toDateString(),
            'amount' => 200,
            'currency' => 'SAR',
            'vendor_name' => 'Old Vendor',
            'status' => 'void',
        ]);

        $this->actingAs($owner)
            ->get(route('expenses.index', ['search' => 'Kitchen pipe']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/expenses/index')
                ->where('expenses.total', 1)
                ->where('expenses.data.0.title', 'Kitchen pipe repair')
                ->where('expenses.data.0.amount', 450)
                ->where('expenses.data.0.asset.title_en', 'Kitchen Unit')
                ->where('expenses.data.0.maintenance_request.title', 'Kitchen leak')
                ->where('expenseInsights.posted_amount', 450)
                ->where('expenseInsights.pending_amount', 300)
                ->where('expenseInsights.void_amount', 200)
                ->where('expenseInsights.maintenance_amount', 450)
                ->where('expenseInsights.linked_to_assets', 1)
                ->where('expenseInsights.linked_to_maintenance', 1)
                ->where('expenseInsights.unlinked_count', 2)
                ->where('categoryOptions.0', 'maintenance'));
    }

    public function test_expense_store_links_maintenance_asset_and_rejects_mismatched_asset(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Linked Asset']);
        $otherAsset = $this->createAsset($portfolio, ['title_en' => 'Wrong Asset']);
        $maintenanceRequest = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'electrical',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Breaker work',
            'description' => 'Breaker needs replacement.',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($owner)
            ->post(route('expenses.store'), [
                'maintenance_request_id' => $maintenanceRequest->id,
                'category' => 'maintenance',
                'title' => 'Breaker replacement',
                'description' => 'Paid technician.',
                'incurred_on' => now()->toDateString(),
                'amount' => 650,
                'currency' => 'SAR',
                'vendor_name' => 'Electric Team',
                'status' => 'posted',
            ]);

        $expense = ExpenseEntry::query()
            ->where('title', 'Breaker replacement')
            ->firstOrFail();

        $response->assertRedirect(route('expenses.show', $expense));

        $this->assertDatabaseHas('expense_entries', [
            'title' => 'Breaker replacement',
            'asset_id' => $asset->id,
            'maintenance_request_id' => $maintenanceRequest->id,
            'status' => 'posted',
        ]);

        $this->actingAs($owner)
            ->post(route('expenses.store'), [
                'asset_id' => $otherAsset->id,
                'maintenance_request_id' => $maintenanceRequest->id,
                'category' => 'maintenance',
                'title' => 'Bad mismatch',
                'incurred_on' => now()->toDateString(),
                'amount' => 100,
                'currency' => 'SAR',
                'status' => 'posted',
            ])
            ->assertStatus(422);
    }

    public function test_dashboard_counts_only_posted_financial_activity(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease($portfolio, $tenant, $this->createAsset($portfolio), $owner);

        foreach ([
            ['reference' => 'POSTED-REV', 'status' => 'posted', 'amount' => 1000],
            ['reference' => 'PENDING-REV', 'status' => 'pending', 'amount' => 2000],
            ['reference' => 'VOID-REV', 'status' => 'void', 'amount' => 3000],
        ] as $payment) {
            Payment::query()->create([
                'portfolio_id' => $portfolio->id,
                'lease_id' => $lease->id,
                'tenant_profile_id' => $tenant->id,
                'recorded_by_user_id' => $owner->id,
                'reference' => $payment['reference'],
                'type' => 'rent',
                'method' => 'cash',
                'status' => $payment['status'],
                'received_on' => now()->toDateString(),
                'amount' => $payment['amount'],
                'currency' => 'SAR',
            ]);
        }

        foreach ([
            ['title' => 'Posted expense', 'status' => 'posted', 'amount' => 250],
            ['title' => 'Pending expense', 'status' => 'pending', 'amount' => 350],
            ['title' => 'Void expense', 'status' => 'void', 'amount' => 450],
        ] as $expense) {
            ExpenseEntry::query()->create([
                'portfolio_id' => $portfolio->id,
                'created_by_user_id' => $owner->id,
                'category' => 'maintenance',
                'title' => $expense['title'],
                'incurred_on' => now()->toDateString(),
                'amount' => $expense['amount'],
                'currency' => 'SAR',
                'status' => $expense['status'],
            ]);
        }

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('stats.monthlyRevenue', 1000)
                ->where('stats.monthlyExpenses', 250));
    }
}
