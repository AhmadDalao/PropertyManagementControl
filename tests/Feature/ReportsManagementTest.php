<?php

namespace Tests\Feature;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\ReportPreset;
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

        $sheetXml = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('Own leak', $sheetXml);
        $this->assertStringNotContainsString('Foreign outage', $sheetXml);
        $this->assertStringNotContainsString('9000', $sheetXml);

        $arabicExport = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('reports.export'))
            ->assertOk();
        $arabicSheet = $this->xlsxWorksheetXml($arabicExport);

        $this->assertStringContainsString('تقرير نظام إدارة العقارات', $arabicSheet);
        $this->assertStringContainsString('طلبات الصيانة المتراكمة', $arabicSheet);
        $this->assertStringContainsString('مفتوح', $arabicSheet);
        $this->assertStringContainsString('مرتفع', $arabicSheet);
        $this->assertStringContainsString('سباكة', $arabicSheet);
        $this->assertStringNotContainsString('Foreign outage', $arabicSheet);
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

    public function test_owner_can_save_and_remove_portfolio_report_presets(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->post(route('reports.presets.store'), [
                'resource' => 'portfolio-report',
                'title_en' => 'Arrears watch',
                'title_ar' => 'متابعة المتأخرات',
                'visibility' => 'portfolio',
                'is_default' => true,
                'filters_json' => [
                    'date_from' => '2026-01-01',
                    'date_to' => '2026-01-31',
                    'preset' => 'arrears',
                ],
            ])
            ->assertRedirect();

        $preset = ReportPreset::query()->firstOrFail();

        $this->assertSame($portfolio->id, $preset->portfolio_id);
        $this->assertSame($owner->id, $preset->user_id);
        $this->assertSame('portfolio-report', $preset->resource);
        $this->assertSame('portfolio', $preset->visibility);
        $this->assertTrue($preset->is_default);
        $this->assertSame('2026-01-01', $preset->filters_json['date_from']);
        $this->assertSame('2026-01-31', $preset->filters_json['date_to']);
        $this->assertArrayNotHasKey('preset', $preset->filters_json);

        $this->actingAs($owner)
            ->get(route('reports.index', [
                'date_from' => '2026-01-01',
                'date_to' => '2026-01-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('savedPresets', 1)
                ->where('savedPresets.0.title_en', 'Arrears watch'));

        $this->actingAs($owner)
            ->delete(route('reports.presets.destroy', $preset))
            ->assertRedirect();

        $this->assertDatabaseMissing('report_presets', ['id' => $preset->id]);
    }

    public function test_only_superadmin_can_create_global_report_presets(): void
    {
        $portfolio = $this->createPortfolio();
        $otherPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $otherOwner = $this->createUserWithRole('owner', $otherPortfolio);
        $superadmin = $this->createUserWithRole('superadmin');

        $payload = [
            'resource' => 'portfolio-report',
            'title_en' => 'Global finance view',
            'title_ar' => 'عرض مالي عام',
            'visibility' => 'global',
            'filters_json' => ['date_from' => '2026-01-01'],
        ];

        $this->actingAs($owner)
            ->post(route('reports.presets.store'), $payload)
            ->assertForbidden();

        $this->assertDatabaseMissing('report_presets', ['title_en' => 'Global finance view']);

        $this->actingAs($superadmin)
            ->post(route('reports.presets.store'), $payload)
            ->assertRedirect();

        $preset = ReportPreset::query()->firstOrFail();

        $this->assertNull($preset->portfolio_id);
        $this->assertSame('global', $preset->visibility);

        $this->actingAs($otherOwner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('savedPresets', 1)
                ->where('savedPresets.0.title_en', 'Global finance view')
                ->where('savedPresets.0.can_delete', false));
    }

    public function test_report_filters_reject_invalid_ranges_and_foreign_portfolios(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->get(route('reports.index', [
                'date_from' => 'not-a-date',
                'date_to' => now()->toDateString(),
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('date_from');

        $this->actingAs($owner)
            ->get(route('reports.index', [
                'date_from' => '2026-02-01',
                'date_to' => '2026-01-01',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('date_to');

        $this->actingAs($owner)
            ->get(route('reports.index', ['portfolio_id' => $foreignPortfolio->id]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('reports.presets.store'), [
                'title_en' => 'Foreign view',
                'title_ar' => 'عرض خارجي',
                'visibility' => 'private',
                'filters_json' => ['portfolio_id' => $foreignPortfolio->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('report_presets', ['title_en' => 'Foreign view']);
    }

    public function test_only_one_personal_report_preset_can_be_default(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $payload = [
            'title_ar' => 'عرض افتراضي',
            'visibility' => 'private',
            'is_default' => true,
            'filters_json' => [
                'date_from' => '2026-02-01',
                'date_to' => '2026-02-28',
            ],
        ];

        $this->actingAs($owner)->post(route('reports.presets.store'), [
            ...$payload,
            'title_en' => 'First default',
        ])->assertRedirect();

        $this->actingAs($owner)->post(route('reports.presets.store'), [
            ...$payload,
            'title_en' => 'Second default',
        ])->assertRedirect();

        $this->assertDatabaseHas('report_presets', [
            'title_en' => 'First default',
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('report_presets', [
            'title_en' => 'Second default',
            'is_default' => true,
        ]);

        $redirect = $this->actingAs($owner)
            ->get(route('reports.index', ['tab' => 'costs']))
            ->assertRedirect();
        $location = (string) $redirect->headers->get('Location');

        $this->assertStringContainsString('date_from=2026-02-01', $location);
        $this->assertStringContainsString('date_to=2026-02-28', $location);
        $this->assertStringContainsString('tab=costs', $location);
    }
}
