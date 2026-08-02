<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\CollectionFollowUp;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Reports\Actions\ArrearsAgingPdfExport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

class ArrearsAgingReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_owner_sees_exact_aging_buckets_balances_and_action_links(): void
    {
        CarbonImmutable::setTestNow('2026-08-15 12:00:00');
        [$owner, $lease, $property] = $this->agingLease();
        $manager = $this->createUserWithRole('property_manager', $lease->portfolio);
        $installments = collect([
            $this->installment($lease, 1, 10, 1000, 100),
            $this->installment($lease, 2, 40, 2000, 500),
            $this->installment($lease, 3, 70, 3000, 0),
            $this->installment($lease, 4, 100, 4000, 1000),
        ]);
        $this->installment($lease, 5, -10, 5000, 0);
        CollectionFollowUp::query()->create([
            'portfolio_id' => $lease->portfolio_id,
            'lease_id' => $lease->id,
            'lease_installment_id' => $installments[3]->id,
            'recorded_by_user_id' => $owner->id,
            'assigned_to_user_id' => $manager->id,
            'contact_method' => 'phone',
            'outcome' => 'promise_to_pay',
            'contacted_at' => now(),
            'promised_amount' => 3000,
            'promised_on' => today()->subDay(),
            'next_follow_up_on' => today(),
            'note' => 'Tenant promise requires follow-up.',
        ]);
        $this->foreignOverdueLease();

        $this->actingAs($owner)
            ->get(route('reports.arrears-aging.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/arrears-aging')
                ->where('records.total', 4)
                ->where('insights', [
                    'installments' => 4,
                    'leases' => 1,
                    'tenants' => 1,
                    'oldest_days' => 100,
                ])
                ->where('counts.0.value', 4)
                ->where('counts.1.value', 1)
                ->where('counts.2.value', 1)
                ->where('counts.3.value', 1)
                ->where('counts.4.value', 1)
                ->where('currencyPositions.0.currency', 'SAR')
                ->where('currencyPositions.0', fn ($position): bool => (float) $position['total'] === 8400.0
                    && (float) $position['days_1_30'] === 900.0
                    && (float) $position['days_31_60'] === 1500.0
                    && (float) $position['days_61_90'] === 3000.0
                    && (float) $position['over_90'] === 3000.0)
                ->where(
                    'records.data',
                    fn ($records): bool => $records->every(
                        fn (array $record): bool => $record['property']['id'] === $property->id
                            && $record['links']['follow_up'] === '/rent-collection/'.$record['id'].'/follow-up'
                            && $record['links']['lease'] === '/leases/'.$lease->id,
                    ) && $records->contains(
                        fn (array $record): bool => $record['id'] === $installments[3]->id
                            && $record['bucket'] === 'over_90'
                            && $record['follow_up']['state'] === 'broken'
                            && $record['follow_up']['assigned_to']['name'] === $manager->name,
                    ),
                ));
    }

    public function test_bucket_search_property_and_pagination_share_one_query_contract(): void
    {
        CarbonImmutable::setTestNow('2026-08-15 12:00:00');
        [$owner, $lease, $property] = $this->agingLease();

        foreach (range(1, 12) as $sequence) {
            $installment = $this->installment($lease, $sequence, 40, 1000, 0);
            $installment->update(['label' => sprintf('August arrears %02d', $sequence)]);
        }

        $this->actingAs($owner)
            ->get(route('reports.arrears-aging.index', [
                'bucket' => 'days_31_60',
                'search' => 'August arrears',
                'property_id' => $property->id,
                'per_page' => 10,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 12)
                ->where('records.current_page', 2)
                ->has('records.data', 2)
                ->where('filters.bucket', 'days_31_60')
                ->where('filters.search', 'August arrears')
                ->where('filters.property_id', $property->id)
                ->where(
                    'downloads.xlsx',
                    fn (string $href): bool => str_contains($href, 'bucket=days_31_60')
                        && str_contains($href, 'search=August%20arrears')
                        && str_contains($href, 'property_id='.$property->id),
                ));
    }

    public function test_manager_is_limited_to_assigned_property_and_tenant_is_denied(): void
    {
        CarbonImmutable::setTestNow('2026-08-15 12:00:00');
        [$owner, $lease, $property] = $this->agingLease();
        $manager = $this->createUserWithRole('property_manager', $lease->portfolio);
        $tenant = $lease->tenantProfile->user;
        $this->assignManagerToAsset($manager, $property);
        $assigned = $this->installment($lease, 1, 20, 1000, 0);
        $this->foreignOverdueLease();

        $this->actingAs($manager)
            ->get(route('reports.arrears-aging.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.id', $assigned->id));

        $this->actingAs($tenant)
            ->get(route('reports.arrears-aging.index'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('reports.arrears-aging.index', ['locale' => 'ar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('counts.0.label', 'كل المتأخرات'));
    }

    public function test_exports_are_real_scoped_pdf_docx_and_two_sheet_xlsx_files(): void
    {
        CarbonImmutable::setTestNow('2026-08-15 12:00:00');
        $this->assertSame(100, ArrearsAgingPdfExport::MAX_DETAIL_ROWS);
        [$owner, $lease] = $this->agingLease();
        $this->installment($lease, 1, 20, 1500, 500);
        $this->foreignOverdueLease();

        $pdf = $this->actingAs($owner)
            ->get(route('reports.arrears-aging.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertSame('%PDF-', substr($pdf->streamedContent(), 0, 5));

        $word = $this->actingAs($owner)
            ->get(route('reports.arrears-aging.word'))
            ->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            (string) $word->headers->get('content-type'),
        );
        $this->assertSame('PK', substr($word->streamedContent(), 0, 2));

        [$workbook, $detail] = $this->workbookSheets(
            $this->actingAs($owner)->get(route('reports.arrears-aging.workbook')),
        );
        $this->assertStringContainsString('Aging Summary', $workbook);
        $this->assertStringContainsString('Aging Detail', $workbook);
        $this->assertStringContainsString('AGING-LEASE', $detail);
        $this->assertStringNotContainsString('FOREIGN-AGING', $detail);
    }

    public function test_report_library_exposes_the_aging_report_and_all_downloads(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('reportLibrary.1.cards.1.key', 'arrears-aging')
                ->where('reportLibrary.1.cards.1.openHref', '/reports/arrears-aging')
                ->has('reportLibrary.1.cards.1.downloads', 3)
                ->where('reportLibrary.1.cards.1.downloads.0.label', 'Download PDF')
                ->where('reportLibrary.1.cards.1.downloads.1.label', 'Download DOCX')
                ->where('reportLibrary.1.cards.1.downloads.2.label', 'Download XLSX'));
    }

    public function test_disabled_payment_module_removes_and_denies_the_aging_report(): void
    {
        $portfolio = $this->createPortfolio([
            'module_settings' => ['payments' => false],
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where(
                'reportLibrary',
                fn ($groups): bool => collect($groups)
                    ->flatMap(fn (array $group) => $group['cards'])
                    ->doesntContain(fn (array $card): bool => $card['key'] === 'arrears-aging'),
            ));

        $this->actingAs($owner)
            ->get(route('reports.arrears-aging.index'))
            ->assertForbidden();
    }

    /** @return array{0:User,1:Lease,2:Asset} */
    private function agingLease(): array
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'Aging Portfolio',
            'name_ar' => 'محفظة المتأخرات',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $property = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'rentable' => false,
            'title_en' => 'Aging Tower',
            'title_ar' => 'برج المتأخرات',
            'code' => 'AGING-TOWER',
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'title_en' => 'Aging Suite',
            'title_ar' => 'جناح المتأخرات',
            'code' => 'AGING-SUITE',
            'occupancy_status' => 'occupied',
        ]);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Aging Tenant']),
        );
        $lease = $this->createLease($portfolio, $tenant, $unit, $owner, [
            'code' => 'AGING-LEASE',
        ], syncInstallments: false);

        return [$owner, $lease, $property];
    }

    private function installment(
        Lease $lease,
        int $sequence,
        int $daysOverdue,
        float $due,
        float $paid,
    ): LeaseInstallment {
        return LeaseInstallment::query()->create([
            'lease_id' => $lease->id,
            'sequence' => $sequence,
            'line_type' => 'rent',
            'label' => "Rent {$sequence}",
            'due_date' => today()->subDays($daysOverdue)->toDateString(),
            'amount_due' => $due,
            'amount_paid' => $paid,
            'status' => $paid > 0 ? 'partial' : 'pending',
        ]);
    }

    private function foreignOverdueLease(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $asset = $this->createAsset($portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner, [
            'code' => 'FOREIGN-AGING',
        ], syncInstallments: false);
        $this->installment($lease, 1, 30, 9000, 0);
    }

    /** @return array{0:string,1:string} */
    private function workbookSheets(TestResponse $response): array
    {
        $response->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertSame('PK', substr((string) file_get_contents($path), 0, 2));
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $workbook = (string) $zip->getFromName('xl/workbook.xml');
        $detail = (string) $zip->getFromName('xl/worksheets/sheet2.xml');
        $zip->close();

        return [$workbook, $detail];
    }
}
