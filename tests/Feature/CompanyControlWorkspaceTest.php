<?php

namespace Tests\Feature;

use App\Models\AssetStakeholder;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\ShowcaseDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

final class CompanyControlWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-08-15 12:00:00');
    }

    public function test_superadmin_can_compare_live_client_health_without_showcase_leakage(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $live = $this->operatingPortfolio('LIVE-CLIENT', 'Live Client', 'عميل فعلي');
        $showcase = $this->showcasePortfolio();

        $this->actingAs($superadmin)
            ->get(route('company-control.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/company-control/index')
                ->where('filters.data_source', 'live')
                ->where('filters.status', 'active')
                ->where('summary.portfolios', 1)
                ->where('summary.properties', 1)
                ->where('summary.active_accounts', 3)
                ->where('portfolios.total', 1)
                ->where('portfolios.data.0.id', $live)
                ->where('portfolios.data.0.code', 'LIVE-CLIENT')
                ->where('portfolios.data.0.properties', 1)
                ->where('portfolios.data.0.rentable_units', 1)
                ->where('portfolios.data.0.occupied_units', 1)
                ->where('portfolios.data.0.active_leases', 1)
                ->where('portfolios.data.0.open_requests', 1)
                ->where('portfolios.data.0.readiness.score', 100)
                ->where('portfolios.data.0.attention', 'risk')
                ->where('portfolios.data', fn (mixed $rows): bool => ! collect($rows)
                    ->pluck('id')
                    ->contains($showcase)));
    }

    public function test_company_control_filters_pages_localizes_and_exports_the_same_scope(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $live = $this->operatingPortfolio('LIVE-FILTER', 'Filtered Client', 'عميل التصفية');
        $showcase = $this->showcasePortfolio();

        foreach (range(1, 13) as $index) {
            $this->createPortfolio([
                'code' => "PAGE-{$index}",
                'name_en' => "Paged Client {$index}",
                'name_ar' => "عميل الصفحة {$index}",
            ]);
        }

        $this->actingAs($superadmin)
            ->get(route('company-control.index', [
                'data_source' => 'showcase',
                'status' => 'active',
                'search' => 'SHOWCASE',
                'attention' => 'risk',
                'sort' => 'valuation',
                'direction' => 'asc',
                'per_page' => 24,
                'locale' => 'ar',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.translations.company_control.title', 'تحكم الشركة')
                ->where('filters.data_source', 'showcase')
                ->where('filters.direction', 'asc')
                ->where('portfolios.total', 1)
                ->where('portfolios.data.0.id', $showcase)
                ->where('portfolios.data', fn (mixed $rows): bool => ! collect($rows)
                    ->pluck('id')
                    ->contains($live)));

        $this->actingAs($superadmin)
            ->get(route('company-control.index', ['per_page' => 12]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('portfolios.total', 14)
                ->has('portfolios.data', 12)
                ->where('portfolios.last_page', 2));

        $response = $this->actingAs($superadmin)
            ->get(route('company-control.export', [
                'data_source' => 'live',
                'status' => 'active',
                'search' => 'Filtered Client',
            ]))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );
        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', substr((string) file_get_contents($path), 0, 2));
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $workbook = (string) $zip->getFromName('xl/workbook.xml');
        $portfolios = (string) $zip->getFromName('xl/worksheets/sheet2.xml');
        $zip->close();

        foreach (['Summary', 'Portfolios', 'Financial', 'Valuation'] as $sheet) {
            $this->assertStringContainsString('name="'.$sheet.'"', $workbook);
        }
        $this->assertStringContainsString('LIVE-FILTER', $portfolios);
        $this->assertStringNotContainsString('SHOWCASE-CLIENT', $portfolios);
    }

    public function test_company_control_is_superadmin_only(): void
    {
        $portfolio = $this->createPortfolio();

        foreach (['owner', 'property_manager', 'tenant'] as $role) {
            $actor = $this->createUserWithRole($role, $portfolio);

            $this->actingAs($actor)
                ->get(route('company-control.index'))
                ->assertForbidden();
            $this->actingAs($actor)
                ->get(route('company-control.export'))
                ->assertForbidden();
        }
    }

    private function operatingPortfolio(
        string $code,
        string $nameEn,
        string $nameAr,
    ): int {
        $portfolio = $this->createPortfolio([
            'code' => $code,
            'name_en' => $nameEn,
            'name_ar' => $nameAr,
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $property = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'rentable' => false,
            'code' => "{$code}-BLD",
            'valuation_amount' => 1000000,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'code' => "{$code}-U1",
            'occupancy_status' => 'occupied',
            'valuation_amount' => 250000,
        ]);

        foreach ([
            ['relationship_type' => 'owner', 'user_id' => $owner->id],
            ['relationship_type' => 'manager', 'user_id' => $manager->id],
        ] as $stakeholder) {
            AssetStakeholder::query()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $property->id,
                'is_primary' => true,
                ...$stakeholder,
            ]);
        }

        $lease = $this->createLease($portfolio, $tenant, $unit, $manager, [
            'terms_json' => ['en' => 'English terms', 'ar' => 'شروط عربية'],
        ]);
        $lease->installments()->firstOrFail()->update([
            'due_date' => today()->subDay(),
            'amount_due' => 2000,
            'amount_paid' => 500,
        ]);
        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => "{$code}-PAY",
            'status' => 'posted',
            'received_on' => today(),
            'amount' => 500,
            'currency' => 'SAR',
        ]);
        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'created_by_user_id' => $manager->id,
            'category' => 'maintenance',
            'title' => 'Repair',
            'incurred_on' => today(),
            'amount' => 100,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);
        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $unit->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'plumbing',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Water leak',
            'description' => 'Kitchen leak',
            'requested_at' => now(),
        ]);

        return $portfolio->id;
    }

    private function showcasePortfolio(): int
    {
        $dataset = ShowcaseDataset::query()->create([
            'key' => 'COMPANY-CONTROL-SHOWCASE',
            'name' => 'Company control showcase',
            'status' => 'completed',
            'target_properties' => 1,
            'generated_properties' => 1,
        ]);

        return $this->createPortfolio([
            'code' => 'SHOWCASE-CLIENT',
            'name_en' => 'Showcase Client',
            'name_ar' => 'عميل استعراضي',
            'showcase_dataset_id' => $dataset->id,
        ])->id;
    }
}
