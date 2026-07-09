<?php

namespace Tests\Feature;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_report_summary_and_export_do_not_leak_foreign_portfolio_data(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);

        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $this->createUserWithRole('tenant', $portfolio, ['name' => 'Own Tenant'])),
            $this->createAsset($portfolio, ['title_en' => 'Own Unit', 'occupancy_status' => 'occupied']),
            $owner,
        );
        $foreignLease = $this->createLease(
            $foreignPortfolio,
            $this->createTenantProfile($foreignPortfolio, $this->createUserWithRole('tenant', $foreignPortfolio, ['name' => 'Foreign Tenant'])),
            $this->createAsset($foreignPortfolio, ['title_en' => 'Foreign Unit', 'occupancy_status' => 'occupied']),
            $foreignOwner,
        );

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $lease->tenant_profile_id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'OWN-PAY-1',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 1000,
            'currency' => 'SAR',
        ]);
        Payment::query()->create([
            'portfolio_id' => $foreignPortfolio->id,
            'lease_id' => $foreignLease->id,
            'tenant_profile_id' => $foreignLease->tenant_profile_id,
            'recorded_by_user_id' => $foreignOwner->id,
            'reference' => 'FOREIGN-PAY-1',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 9000,
            'currency' => 'SAR',
        ]);

        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $lease->leaseable_id,
            'created_by_user_id' => $owner->id,
            'category' => 'plumbing',
            'title' => 'Own repair',
            'incurred_on' => now()->toDateString(),
            'amount' => 250,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);
        ExpenseEntry::query()->create([
            'portfolio_id' => $foreignPortfolio->id,
            'asset_id' => $foreignLease->leaseable_id,
            'created_by_user_id' => $foreignOwner->id,
            'category' => 'electrical',
            'title' => 'Foreign repair',
            'incurred_on' => now()->toDateString(),
            'amount' => 4000,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);

        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $lease->leaseable_id,
            'tenant_profile_id' => $lease->tenant_profile_id,
            'submitted_by_user_id' => $owner->id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'Own leak',
            'description' => 'Kitchen sink leak',
            'requested_at' => now(),
        ]);
        MaintenanceRequest::query()->create([
            'portfolio_id' => $foreignPortfolio->id,
            'asset_id' => $foreignLease->leaseable_id,
            'tenant_profile_id' => $foreignLease->tenant_profile_id,
            'submitted_by_user_id' => $foreignOwner->id,
            'category' => 'electrical',
            'priority' => 'urgent',
            'status' => 'open',
            'title' => 'Foreign outage',
            'description' => 'Should never appear',
            'requested_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/index')
                ->where('summary.revenue', fn (int|float $value) => (float) $value === 1000.0)
                ->where('summary.expenses', fn (int|float $value) => (float) $value === 250.0)
                ->where('summary.net', fn (int|float $value) => (float) $value === 750.0)
                ->where('summary.openRequests', 1)
                ->has('maintenanceBacklog', 1)
                ->where('maintenanceBacklog.0.title', 'Own leak'));

        $export = $this->actingAs($owner)
            ->get(route('reports.export'))
            ->assertOk();

        $csv = $export->streamedContent();

        $this->assertStringContainsString('Own leak', $csv);
        $this->assertStringNotContainsString('Foreign outage', $csv);
        $this->assertStringNotContainsString('9000', $csv);
    }

    public function test_report_date_filters_limit_financial_activity(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $this->createUserWithRole('tenant', $portfolio)),
            $this->createAsset($portfolio),
            $owner,
        );

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $lease->tenant_profile_id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'TODAY-PAY',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 500,
            'currency' => 'SAR',
        ]);
        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $lease->tenant_profile_id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'OLD-PAY',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->subYear()->toDateString(),
            'amount' => 700,
            'currency' => 'SAR',
        ]);

        $this->actingAs($owner)
            ->get(route('reports.index', [
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.revenue', fn (int|float $value) => (float) $value === 500.0)
                ->has('recentPayments', 1)
                ->where('recentPayments.0.reference', 'TODAY-PAY'));
    }

    public function test_tenant_cannot_access_operational_reports_or_exports(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        $this->actingAs($tenant)
            ->get(route('reports.index'))
            ->assertForbidden();

        $this->actingAs($tenant)
            ->get(route('reports.export'))
            ->assertForbidden();
    }
}
