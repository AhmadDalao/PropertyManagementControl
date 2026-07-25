<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PropertyManagerAssignmentScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_manager_records_totals_search_and_exports_follow_assigned_properties(): void
    {
        $data = $this->fixture();
        $manager = $data['manager'];

        foreach ([
            ['assets.index', 'assets', 2],
            ['tenants.index', 'tenants', 1],
            ['leases.index', 'leases', 1],
            ['payments.index', 'payments', 1],
            ['maintenance-requests.index', 'requests', 1],
            ['expenses.index', 'expenses', 1],
            ['documents.index', 'documents', 1],
        ] as [$route, $prop, $total]) {
            $this->actingAs($manager)
                ->get(route($route, ['per_page' => 100]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where("{$prop}.total", $total));
        }

        $this->actingAs($manager)
            ->get(route('portfolios.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('portfolios.data.0.assets_count', 2)
                ->where('portfolios.data.0.leases_count', 1)
                ->where('portfolios.data.0.active_leases_count', 1)
                ->where('portfolioInsights.assets', 2)
                ->where('portfolioInsights.leases', 1)
                ->where('portfolioInsights.posted_revenue_total', 100)
                ->where('portfolioInsights.posted_expense_total', 10));

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('propertyFocus.assignment_restricted', true)
                ->where('propertyFocus.has_assignments', true)
                ->has('propertyFocus.options', 1)
                ->where('propertyFocus.options.0.id', $data['visibleRoot']->id)
                ->where('stats.totalAssets', 2)
                ->where('stats.activeLeases', 1)
                ->where('stats.totalUsers', 2)
                ->where('stats.monthlyRevenue', 100)
                ->where('stats.monthlyExpenses', 10));

        $this->actingAs($manager)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.revenue', fn (int|float $value): bool => (float) $value === 100.0)
                ->where('summary.expenses', fn (int|float $value): bool => (float) $value === 10.0)
                ->where('summary.activeLeases', 1)
                ->where('summary.openRequests', 1)
                ->has('propertyOptions', 1)
                ->where('propertyOptions.0.id', $data['visibleRoot']->id));

        $this->actingAs($manager)
            ->get(route('property-map.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('propertyMap.summary.total', 1)
                ->has('propertyMap.assets', 1)
                ->where('propertyMap.assets.0.id', $data['visibleRoot']->id));

        $this->actingAs($manager)
            ->getJson(route('global-search', ['q' => 'HIDDEN-SCOPE']))
            ->assertOk()
            ->assertJsonPath('direct_url', '')
            ->assertJsonMissing(['title' => 'Hidden Scope Unit']);

        $export = $this->actingAs($manager)->get(route('exports.resource', [
            'resource' => 'assets',
        ]));
        $export->assertOk();
        $sheet = $this->xlsxWorksheetXml($export);
        $this->assertStringContainsString('VISIBLE-SCOPE', $sheet);
        $this->assertStringNotContainsString('HIDDEN-SCOPE', $sheet);
    }

    public function test_manager_direct_routes_and_create_options_reject_same_portfolio_siblings(): void
    {
        $data = $this->fixture();
        $manager = $data['manager'];

        foreach ([
            route('assets.show', $data['hiddenUnit']),
            route('tenants.show', $data['hiddenTenant']),
            route('leases.show', $data['hiddenLease']),
            route('payments.show', $data['hiddenPayment']),
            route('maintenance-requests.show', $data['hiddenMaintenance']),
            route('expenses.show', $data['hiddenExpense']),
            route('documents.show', $data['hiddenDocument']),
        ] as $url) {
            $this->actingAs($manager)->get($url)->assertForbidden();
        }

        $this->actingAs($manager)
            ->get(route('leases.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where(
                'formPage.fields',
                fn ($fields): bool => $this->fieldOptionsContainOnly(
                    $fields,
                    'asset_id',
                    $data['visibleRoot']->id,
                    $data['hiddenRoot']->id,
                ) && $this->fieldOptionsContainOnly(
                    $fields,
                    'tenant_profile_id',
                    $data['visibleTenant']->id,
                    $data['hiddenTenant']->id,
                ),
            ));

        $this->actingAs($manager)
            ->get(route('payments.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where(
                'formPage.fields',
                fn ($fields): bool => $this->fieldOptionsContainOnly(
                    $fields,
                    'lease_id',
                    $data['visibleLease']->id,
                    $data['hiddenLease']->id,
                ),
            ));

        $this->actingAs($manager)
            ->get(route('assets.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where(
                'formPage.fields',
                function ($fields) use ($data): bool {
                    $fields = collect($fields);

                    return $this->fieldOptionsContainOnly(
                        $fields,
                        'parent_id',
                        $data['visibleRoot']->id,
                        $data['hiddenRoot']->id,
                    )
                        && ! $fields->contains('name', 'primary_owner_user_id')
                        && ! $fields->contains('name', 'primary_manager_user_id');
                },
            ));
    }

    public function test_manager_without_assignment_gets_a_deliberate_empty_scope(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $this->createAsset($portfolio, ['title_en' => 'Owner Only Asset']);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('propertyFocus.assignment_restricted', true)
                ->where('propertyFocus.has_assignments', false)
                ->has('propertyFocus.options', 0)
                ->where('stats.totalAssets', 0)
                ->where('stats.totalUsers', 1)
                ->where('nextActions.0.href', '/portfolios'));

        $this->actingAs($manager)
            ->get(route('assets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('assets.total', 0));

        $this->actingAs($manager)
            ->post(route('tenants.store'), [])
            ->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function fixture(): array
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $visibleRoot = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'rentable' => true,
            'title_en' => 'Visible Scope Building',
            'code' => 'VISIBLE-SCOPE-ROOT',
        ]);
        $hiddenRoot = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'rentable' => true,
            'title_en' => 'Hidden Scope Building',
            'code' => 'HIDDEN-SCOPE-ROOT',
        ]);
        $visibleUnit = $this->createAsset($portfolio, [
            'parent_id' => $visibleRoot->id,
            'title_en' => 'Visible Scope Unit',
            'code' => 'VISIBLE-SCOPE-UNIT',
            'occupancy_status' => 'occupied',
        ]);
        $hiddenUnit = $this->createAsset($portfolio, [
            'parent_id' => $hiddenRoot->id,
            'title_en' => 'Hidden Scope Unit',
            'code' => 'HIDDEN-SCOPE-UNIT',
            'occupancy_status' => 'occupied',
        ]);
        AssetStakeholder::query()->create([
            'asset_id' => $visibleRoot->id,
            'portfolio_id' => $portfolio->id,
            'user_id' => $manager->id,
            'relationship_type' => 'manager',
            'is_primary' => true,
        ]);
        $visibleTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Visible Scope Tenant']),
        );
        $hiddenTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Hidden Scope Tenant']),
        );
        $visibleLease = $this->createLease(
            $portfolio,
            $visibleTenant,
            $visibleUnit,
            $manager,
            ['code' => 'VISIBLE-SCOPE-LEASE'],
        );
        $hiddenLease = $this->createLease(
            $portfolio,
            $hiddenTenant,
            $hiddenUnit,
            $owner,
            ['code' => 'HIDDEN-SCOPE-LEASE'],
        );
        $visiblePayment = $this->payment($visibleLease, $manager->id, 100, 'VISIBLE-SCOPE-PAY');
        $hiddenPayment = $this->payment($hiddenLease, $owner->id, 900, 'HIDDEN-SCOPE-PAY');
        $visibleMaintenance = $this->maintenance(
            $visibleUnit,
            $visibleLease,
            $visibleTenant,
            'Visible scope maintenance',
        );
        $hiddenMaintenance = $this->maintenance(
            $hiddenUnit,
            $hiddenLease,
            $hiddenTenant,
            'Hidden scope maintenance',
        );
        $visibleExpense = $this->expense($visibleUnit, $visibleMaintenance, $manager->id, 10, 'Visible scope expense');
        $hiddenExpense = $this->expense($hiddenUnit, $hiddenMaintenance, $owner->id, 90, 'Hidden scope expense');
        $visibleDocument = $this->document($visibleLease, $manager->id, 'Visible scope contract');
        $hiddenDocument = $this->document($hiddenLease, $owner->id, 'Hidden scope contract');

        return compact(
            'portfolio',
            'owner',
            'manager',
            'visibleRoot',
            'hiddenRoot',
            'visibleUnit',
            'hiddenUnit',
            'visibleTenant',
            'hiddenTenant',
            'visibleLease',
            'hiddenLease',
            'visiblePayment',
            'hiddenPayment',
            'visibleMaintenance',
            'hiddenMaintenance',
            'visibleExpense',
            'hiddenExpense',
            'visibleDocument',
            'hiddenDocument',
        );
    }

    private function payment($lease, int $recordedBy, float $amount, string $reference): Payment
    {
        return Payment::query()->create([
            'portfolio_id' => $lease->portfolio_id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $lease->tenant_profile_id,
            'recorded_by_user_id' => $recordedBy,
            'reference' => $reference,
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => today(),
            'amount' => $amount,
            'currency' => 'SAR',
        ]);
    }

    private function maintenance(
        Asset $asset,
        $lease,
        $tenant,
        string $title,
    ): MaintenanceRequest {
        return MaintenanceRequest::query()->create([
            'portfolio_id' => $asset->portfolio_id,
            'asset_id' => $asset->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenant->user_id,
            'category' => 'plumbing',
            'priority' => 'medium',
            'status' => 'open',
            'title' => $title,
            'description' => $title,
            'requested_at' => now(),
        ]);
    }

    private function expense(
        Asset $asset,
        MaintenanceRequest $maintenance,
        int $createdBy,
        float $amount,
        string $title,
    ): ExpenseEntry {
        return ExpenseEntry::query()->create([
            'portfolio_id' => $asset->portfolio_id,
            'asset_id' => $asset->id,
            'maintenance_request_id' => $maintenance->id,
            'created_by_user_id' => $createdBy,
            'title' => $title,
            'category' => 'maintenance',
            'status' => 'posted',
            'amount' => $amount,
            'currency' => 'SAR',
            'incurred_on' => today(),
        ]);
    }

    private function document($lease, int $uploadedBy, string $title): Document
    {
        return Document::query()->create([
            'portfolio_id' => $lease->portfolio_id,
            'uploaded_by_user_id' => $uploadedBy,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'lease_contract',
            'title_en' => $title,
            'title_ar' => 'عقد نطاق',
            'disk' => 'local',
            'file_path' => 'documents/'.str($title)->slug().'.pdf',
            'original_name' => str($title)->slug().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_public' => true,
        ]);
    }

    private function fieldOptionsContainOnly(
        mixed $fields,
        string $field,
        int $visible,
        int $hidden,
    ): bool {
        $options = collect($fields)->firstWhere('name', $field)['options'] ?? [];
        $values = collect($options)->pluck('value')->map(fn (mixed $value): int => (int) $value);

        return $values->contains($visible) && ! $values->contains($hidden);
    }
}
