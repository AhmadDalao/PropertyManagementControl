<?php

namespace Tests\Feature;

use App\Models\LeaseInstallment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RentRollReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_receives_a_current_rentable_schedule_with_vacancies_and_exact_balances(): void
    {
        CarbonImmutable::setTestNow('2026-08-15 12:00:00');

        try {
            $portfolio = $this->createPortfolio([
                'name_en' => 'North Portfolio',
                'name_ar' => 'محفظة الشمال',
            ]);
            $owner = $this->createUserWithRole('owner', $portfolio);
            $property = $this->createAsset($portfolio, [
                'parent_id' => null,
                'asset_type' => 'building',
                'title_en' => 'North Tower',
                'title_ar' => 'برج الشمال',
                'code' => 'NORTH-TOWER',
                'rentable' => false,
            ]);
            $occupied = $this->createAsset($portfolio, [
                'parent_id' => $property->id,
                'title_en' => 'Suite 101',
                'title_ar' => 'جناح 101',
                'code' => 'NORTH-101',
                'occupancy_status' => 'occupied',
            ]);
            $vacant = $this->createAsset($portfolio, [
                'parent_id' => $property->id,
                'title_en' => 'Suite 102',
                'title_ar' => 'جناح 102',
                'code' => 'NORTH-102',
            ]);
            $tenant = $this->createTenantProfile(
                $portfolio,
                $this->createUserWithRole('tenant', $portfolio, [
                    'name' => 'Current Tenant',
                ]),
            );
            $lease = $this->createLease(
                $portfolio,
                $tenant,
                $occupied,
                $owner,
                [
                    'code' => 'LEASE-NORTH-101',
                    'started_at' => '2026-01-01',
                    'ends_at' => '2027-01-01',
                    'rent_amount' => 2000,
                    'deposit_amount' => 1250,
                ],
                syncInstallments: false,
            );

            foreach ([
                [1, '2026-07-01', 2000, 500],
                [2, '2026-09-01', 2000, 0],
            ] as [$sequence, $dueDate, $due, $paid]) {
                LeaseInstallment::query()->create([
                    'lease_id' => $lease->id,
                    'sequence' => $sequence,
                    'line_type' => 'rent',
                    'label' => "Rent {$sequence}",
                    'due_date' => $dueDate,
                    'amount_due' => $due,
                    'amount_paid' => $paid,
                    'status' => $paid > 0 ? 'partial' : 'pending',
                ]);
            }

            $foreignPortfolio = $this->createPortfolio();
            $this->createAsset($foreignPortfolio, [
                'title_en' => 'Foreign Unit',
                'code' => 'FOREIGN-UNIT',
            ]);

            $this->actingAs($owner)
                ->get(route('reports.rent-roll.index'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('admin/reports/rent-roll')
                    ->where('records.total', 2)
                    ->where('insights', [
                        'matching' => 2,
                        'occupied' => 1,
                        'vacant' => 1,
                        'attention' => 1,
                    ])
                    ->where(
                        'records.data',
                        fn ($records): bool => $records->contains(
                            fn (array $record): bool => $record['id'] === $occupied->id
                                && $record['state'] === 'arrears'
                                && $record['lease']['tenant'] === 'Current Tenant'
                                && (float) $record['lease']['total_due'] === 4000.0
                                && (float) $record['lease']['total_paid'] === 500.0
                                && (float) $record['lease']['balance'] === 3500.0
                                && (float) $record['lease']['overdue'] === 1500.0
                                && $record['property']['id'] === $property->id
                        ) && $records->contains(
                            fn (array $record): bool => $record['id'] === $vacant->id
                                && $record['state'] === 'vacant'
                                && $record['lease'] === null
                        ) && $records->doesntContain(
                            fn (array $record): bool => $record['code'] === 'FOREIGN-UNIT'
                        ),
                    )
                    ->where(
                        'currencyPositions.0',
                        fn ($position): bool => $position['currency'] === 'SAR'
                            && $position['active_leases'] === 1
                            && (float) $position['contracted'] === 4000.0
                            && (float) $position['paid'] === 500.0
                            && (float) $position['outstanding'] === 3500.0
                            && (float) $position['overdue'] === 1500.0
                            && (float) $position['deposits'] === 1250.0,
                    )
                    ->where(
                        'scope',
                        fn ($scope): bool => collect($scope)
                            ->keyBy('label')
                            ->get('Portfolio scope')['value'] === 'North Portfolio',
                    ));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_filters_search_pagination_and_property_scope_use_the_same_query_contract(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $property = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'title_en' => 'Directory Tower',
            'code' => 'DIRECTORY-TOWER',
            'rentable' => false,
        ]);

        foreach (range(1, 12) as $index) {
            $this->createAsset($portfolio, [
                'parent_id' => $property->id,
                'title_en' => sprintf('Directory Suite %02d', $index),
                'title_ar' => sprintf('جناح الدليل %02d', $index),
                'code' => sprintf('DIR-%02d', $index),
            ]);
        }

        $this->actingAs($owner)
            ->get(route('reports.rent-roll.index', [
                'property_id' => $property->id,
                'state' => 'vacant',
                'search' => 'Directory',
                'per_page' => 10,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 12)
                ->where('records.current_page', 2)
                ->has('records.data', 2)
                ->where('filters.state', 'vacant')
                ->where('filters.search', 'Directory')
                ->where('filters.property_id', $property->id)
                ->where(
                    'downloads.xlsx',
                    fn (string $href): bool => str_contains($href, 'search=Directory')
                        && str_contains($href, 'state=vacant')
                        && str_contains($href, 'property_id='.$property->id),
                ));

        $this->actingAs($owner)
            ->get(route('reports.rent-roll.index', ['search' => 'DIR-07']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.code', 'DIR-07'));
    }

    public function test_manager_is_limited_to_assigned_properties_and_tenant_is_denied(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $assignedProperty = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'rentable' => false,
            'title_en' => 'Assigned Tower',
        ]);
        $otherProperty = $this->createAsset($portfolio, [
            'parent_id' => null,
            'asset_type' => 'building',
            'rentable' => false,
            'title_en' => 'Other Tower',
        ]);
        $assignedUnit = $this->createAsset($portfolio, [
            'parent_id' => $assignedProperty->id,
            'title_en' => 'Assigned Unit',
            'code' => 'ASSIGNED-UNIT',
        ]);
        $this->createAsset($portfolio, [
            'parent_id' => $otherProperty->id,
            'title_en' => 'Other Unit',
            'code' => 'OTHER-UNIT',
        ]);
        $this->assignManagerToAsset($manager, $assignedProperty);

        $this->actingAs($manager)
            ->get(route('reports.rent-roll.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.id', $assignedUnit->id)
                ->where('records.data.0.code', 'ASSIGNED-UNIT'));

        $this->actingAs($tenant)
            ->get(route('reports.rent-roll.index'))
            ->assertForbidden();
    }

    public function test_rent_roll_exports_are_real_scoped_pdf_docx_and_xlsx_files(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $asset = $this->createAsset($portfolio, [
            'title_en' => 'Export Suite',
            'title_ar' => 'جناح التصدير',
            'code' => 'EXPORT-SUITE',
        ]);
        $foreignPortfolio = $this->createPortfolio();
        $this->createAsset($foreignPortfolio, [
            'title_en' => 'Foreign Export Suite',
            'code' => 'FOREIGN-EXPORT',
        ]);

        $pdf = $this->actingAs($owner)
            ->get(route('reports.rent-roll.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertSame('%PDF-', substr($pdf->streamedContent(), 0, 5));

        $word = $this->actingAs($owner)
            ->get(route('reports.rent-roll.word'))
            ->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            (string) $word->headers->get('content-type'),
        );
        $this->assertSame('PK', substr($word->streamedContent(), 0, 2));

        $worksheet = $this->xlsxWorksheetXml(
            $this->actingAs($owner)->get(route('reports.rent-roll.workbook')),
        );
        $this->assertStringContainsString($asset->code, $worksheet);
        $this->assertStringNotContainsString('FOREIGN-EXPORT', $worksheet);
    }

    public function test_report_library_exposes_the_current_rent_roll_and_all_download_formats(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('reportLibrary.0.cards.2.key', 'rent-roll')
                ->where('reportLibrary.0.cards.2.openHref', '/reports/rent-roll')
                ->has('reportLibrary.0.cards.2.downloads', 3)
                ->where('reportLibrary.0.cards.2.downloads.0.label', 'Download PDF')
                ->where('reportLibrary.0.cards.2.downloads.1.label', 'Download DOCX')
                ->where('reportLibrary.0.cards.2.downloads.2.label', 'Download XLSX'));
    }
}
