<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PropertyOperationsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_property_detail_rolls_descendant_unit_operations_into_one_owner_view(): void
    {
        $this->travelTo('2026-08-15 12:00:00');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Tower Tenant']);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Operations Tower',
            'title_ar' => 'برج العمليات',
            'code' => 'OPS-TOWER',
            'rentable' => false,
            'valuation_amount' => 5000000,
        ]);
        $floor = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'asset_type' => 'floor',
            'title_en' => 'Floor 01',
            'title_ar' => 'الطابق 01',
            'code' => 'OPS-F01',
            'rentable' => false,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $floor->id,
            'asset_type' => 'unit',
            'title_en' => 'Unit 101',
            'title_ar' => 'الوحدة 101',
            'code' => 'OPS-U101',
            'rentable' => true,
            'occupancy_status' => 'occupied',
        ]);
        $lease = $this->createLease($portfolio, $tenant, $unit, $owner, [
            'code' => 'OPS-LEASE',
        ], false);
        LeaseInstallment::query()->create([
            'lease_id' => $lease->id,
            'sequence' => 1,
            'line_type' => 'rent',
            'label' => 'Current month rent',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => today()->subDay()->toDateString(),
            'amount_due' => 2000,
            'amount_paid' => 500,
            'status' => 'partial',
        ]);
        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'OPS-PAYMENT',
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => today()->toDateString(),
            'amount' => 500,
            'currency' => 'SAR',
        ]);
        $maintenance = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'Kitchen leak',
            'description' => 'Leak below the sink.',
            'requested_at' => now(),
        ]);
        $expense = ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'created_by_user_id' => $owner->id,
            'title' => 'Plumber callout',
            'category' => 'plumbing',
            'status' => 'posted',
            'amount' => 100,
            'currency' => 'SAR',
            'incurred_on' => today()->toDateString(),
        ]);
        Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => Asset::class,
            'documentable_id' => $unit->id,
            'type' => 'property_document',
            'title_en' => 'Unit inspection',
            'title_ar' => 'فحص الوحدة',
            'disk' => 'local',
            'file_path' => 'documents/unit-inspection.pdf',
            'original_name' => 'unit-inspection.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 128,
        ]);
        Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease::class,
            'documentable_id' => $lease->id,
            'type' => 'lease_contract',
            'title_en' => 'Lease contract',
            'title_ar' => 'عقد الإيجار',
            'disk' => 'local',
            'file_path' => 'documents/lease-contract.pdf',
            'original_name' => 'lease-contract.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 256,
        ]);

        $this->actingAs($owner)
            ->get(route('assets.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-show')
                ->where(
                    'detailPage.header.actions.2.href',
                    route('reports.properties.show', $property),
                )
                ->has('detailPage.header.actions', 3)
                ->where('detailPage.workflow.title', 'Collect overdue rent first')
                ->where('detailPage.workflow.status', '1,500.00 SAR')
                ->where('detailPage.stats.1.value', '100.0%')
                ->where('detailPage.stats.2.value', 1)
                ->where('detailPage.stats.3.value', 1)
                ->where('detailPage.stats.4.value', '1,500.00 SAR')
                ->where('detailPage.stats.5.value', '400.00 SAR')
                ->where('detailPage.decisionCards.1.value', '25.0%')
                ->where('detailPage.related.0.rows.0.Unit / space', 'Unit 101')
                ->where('detailPage.related.1.rows.0.Asset', 'Floor 01')
                ->where('detailPage.related.2.rows.0.Lease', 'OPS-LEASE')
                ->where('detailPage.related.3.rows.0.Remaining', '1,500.00 SAR')
                ->where('detailPage.related.4.rows.0.Request', '#'.$maintenance->id.' Kitchen leak')
                ->where('detailPage.related.5.rows.0.Expense', $expense->title)
                ->has('detailPage.documents', 2)
                ->where('detailPage.documents', fn ($documents) => collect($documents)
                    ->pluck('title')
                    ->sort()
                    ->values()
                    ->all() === ['Lease contract', 'Unit inspection'])
            );
    }

    public function test_property_filter_scopes_registers_metrics_and_xlsx_exports(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $first = $this->createPropertyDataset($portfolio, $owner, 'FIRST');
        $second = $this->createPropertyDataset($portfolio, $owner, 'SECOND');

        foreach ([
            ['assets.index', 'assets.total', 2],
            ['tenants.index', 'tenants.total', 1],
            ['leases.index', 'leases.total', 1],
            ['payments.index', 'payments.total', 1],
            ['maintenance-requests.index', 'requests.total', 1],
            ['expenses.index', 'expenses.total', 1],
        ] as [$routeName, $path, $total]) {
            $this->actingAs($owner)
                ->get(route($routeName, ['property_id' => $first['property']->id]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('filters.property_id', (string) $first['property']->id)
                    ->where($path, $total));
        }

        $export = $this->actingAs($owner)->get(route('exports.resource', [
            'resource' => 'payments',
            'property_id' => $first['property']->id,
        ]));
        $export->assertOk();
        $sheet = $this->xlsxWorksheetXml($export);
        $this->assertStringContainsString($first['payment']->reference, $sheet);
        $this->assertStringNotContainsString($second['payment']->reference, $sheet);

        $foreignPortfolio = $this->createPortfolio();
        $foreignProperty = $this->createAsset($foreignPortfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'rentable' => false,
        ]);
        $this->actingAs($owner)
            ->get(route('assets.index', ['property_id' => $foreignProperty->id]))
            ->assertForbidden();
    }

    public function test_arabic_property_filter_options_use_arabic_names(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'English Portfolio',
            'name_ar' => 'المحفظة العربية',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio, [
            'preferred_locale' => 'ar',
        ]);
        $property = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'title_en' => 'English Tower',
            'title_ar' => 'البرج العربي',
            'code' => 'AR-TOWER',
            'rentable' => false,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('assets.index', ['property_id' => $property->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.translations.filters.property', 'العقار')
                ->where('app.translations.filters.all_properties', 'جميع العقارات')
                ->where('propertyOptions.0.name', 'البرج العربي · AR-TOWER')
                ->where('filters.property_id', (string) $property->id));
    }

    /**
     * @return array{property:Asset,payment:Payment}
     */
    private function createPropertyDataset(Portfolio $portfolio, User $owner, string $prefix): array
    {
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, [
            'name' => "{$prefix} Tenant",
        ]);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $property = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'title_en' => "{$prefix} Property",
            'title_ar' => "عقار {$prefix}",
            'code' => "{$prefix}-PROPERTY",
            'rentable' => false,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'asset_type' => 'unit',
            'title_en' => "{$prefix} Unit",
            'title_ar' => "وحدة {$prefix}",
            'code' => "{$prefix}-UNIT",
            'rentable' => true,
            'occupancy_status' => 'occupied',
        ]);
        $lease = $this->createLease($portfolio, $tenant, $unit, $owner, [
            'code' => "{$prefix}-LEASE",
        ]);
        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => "{$prefix}-PAYMENT",
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => today()->toDateString(),
            'amount' => 1000,
            'currency' => 'SAR',
        ]);
        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'general',
            'priority' => 'low',
            'status' => 'open',
            'title' => "{$prefix} Request",
            'description' => 'Property filter test.',
            'requested_at' => now(),
        ]);
        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'created_by_user_id' => $owner->id,
            'title' => "{$prefix} Expense",
            'category' => 'maintenance',
            'status' => 'posted',
            'amount' => 100,
            'currency' => 'SAR',
            'incurred_on' => today()->toDateString(),
        ]);

        return compact('property', 'payment');
    }
}
