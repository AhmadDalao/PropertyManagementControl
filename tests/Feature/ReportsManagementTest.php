<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\ReportPreset;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

class ReportsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_payload_contract_stays_stable_for_dashboard_and_workbook(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'Owner Operations',
            'name_ar' => 'عمليات المالك',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/index')
                ->where('mode', 'portfolio')
                ->hasAll([
                    'summary.currency',
                    'summary.currencyCount',
                    'summary.currencyTotals',
                    'summary.revenue',
                    'summary.expenses',
                    'summary.net',
                    'summary.scheduledDue',
                    'summary.scheduledPaid',
                    'summary.collectionRate',
                    'summary.occupancyRate',
                    'summary.arrears',
                    'summary.contractBalance',
                    'summary.activeLeases',
                    'summary.leasesInArrears',
                    'summary.openRequests',
                    'summary.resolvedRequests',
                    'summary.openCollectionCount',
                    'summary.untrackedOverdueCount',
                    'summary.followUpDueCount',
                    'summary.brokenPromisesCount',
                    'comparison.period.date_from',
                    'comparison.period.date_to',
                    'comparison.currencyPositions',
                    'comparison.serviceMetrics',
                    'charts.revenueByMonth',
                    'charts.expenseByCategory',
                    'charts.assetMix',
                    'charts.maintenanceByStatus',
                    'arrearsLeases',
                    'topAssets',
                    'recentPayments',
                    'recentExpenses',
                    'maintenanceBacklog',
                    'journalSummary.totalEvents',
                    'journalSummary.newLeases',
                    'journalSummary.serviceOpened',
                    'journalSummary.serviceResolved',
                    'journalSummary.documentsAdded',
                    'operationalJournal',
                ])
                ->has('reportLibrary', 4)
                ->where('reportLibrary.0.key', 'owner')
                ->where('reportLibrary.0.cards.0.key', 'owner-statement')
                ->where('reportLibrary.0.cards.0.downloads.0.label', 'Download PDF')
                ->where('reportLibrary.0.cards.0.downloads.1.label', 'Download DOCX')
                ->where('reportLibrary.0.cards.0.downloads.2.label', 'Download XLSX')
                ->where(
                    'reportLibrary.0.cards.0.downloads.2.href',
                    fn (string $url): bool => str_contains($url, '/reports/statement.xlsx'),
                )
                ->where(
                    'reportLibrary.0.cards.0.scope',
                    fn ($scope): bool => collect($scope)->keyBy('label')->get('Portfolio scope')['value'] === 'Owner Operations'
                        && collect($scope)->keyBy('label')->get('Property scope')['value'] === 'All properties'
                        && str_contains(
                            collect($scope)->keyBy('label')->get('Selected period')['value'],
                            'Custom dates',
                        ),
                )
                ->where('reportLibrary.1.key', 'finance')
                ->where('reportLibrary.2.key', 'operations')
                ->where('reportLibrary.3.key', 'control'));
    }

    public function test_reports_compare_the_previous_equivalent_period_and_export_the_evidence(): void
    {
        CarbonImmutable::setTestNow('2026-08-15 12:00:00');

        try {
            $portfolio = $this->createPortfolio();
            $owner = $this->createUserWithRole('owner', $portfolio);
            $asset = $this->createAsset($portfolio, [
                'title_en' => 'Comparison Unit',
                'occupancy_status' => 'occupied',
            ]);
            $tenant = $this->createTenantProfile(
                $portfolio,
                $this->createUserWithRole('tenant', $portfolio),
            );
            $lease = $this->createLease(
                $portfolio,
                $tenant,
                $asset,
                $owner,
                [
                    'started_at' => '2026-07-01',
                    'ends_at' => '2026-09-30',
                    'rent_amount' => 2000,
                ],
                syncInstallments: false,
            );

            foreach ([
                [1, '2026-07-05', 1000],
                [2, '2026-08-05', 1500],
            ] as [$sequence, $dueDate, $amountPaid]) {
                LeaseInstallment::query()->create([
                    'lease_id' => $lease->id,
                    'sequence' => $sequence,
                    'line_type' => 'rent',
                    'label' => "Rent {$sequence}",
                    'due_date' => $dueDate,
                    'amount_due' => 2000,
                    'amount_paid' => $amountPaid,
                    'status' => 'partial',
                ]);
            }

            foreach ([
                ['PREVIOUS-COLLECTION', '2026-07-10', 1000],
                ['CURRENT-COLLECTION', '2026-08-10', 1500],
            ] as [$reference, $receivedOn, $amount]) {
                Payment::query()->create([
                    'portfolio_id' => $portfolio->id,
                    'lease_id' => $lease->id,
                    'tenant_profile_id' => $tenant->id,
                    'recorded_by_user_id' => $owner->id,
                    'reference' => $reference,
                    'type' => 'rent',
                    'method' => 'bank_transfer',
                    'status' => 'posted',
                    'received_on' => $receivedOn,
                    'amount' => $amount,
                    'currency' => 'SAR',
                ]);
            }

            foreach ([
                ['Previous repair', '2026-07-08', 400],
                ['Current repair', '2026-08-08', 300],
            ] as [$title, $incurredOn, $amount]) {
                ExpenseEntry::query()->create([
                    'portfolio_id' => $portfolio->id,
                    'asset_id' => $asset->id,
                    'created_by_user_id' => $owner->id,
                    'category' => 'maintenance',
                    'title' => $title,
                    'incurred_on' => $incurredOn,
                    'amount' => $amount,
                    'currency' => 'SAR',
                    'status' => 'posted',
                ]);
            }

            foreach ([
                ['Previous request', '2026-07-09 09:00:00', null],
                ['Current request', '2026-08-09 09:00:00', null],
                ['Current resolved', '2026-08-11 09:00:00', '2026-08-12 09:00:00'],
            ] as [$title, $createdAt, $resolvedAt]) {
                MaintenanceRequest::query()->create([
                    'portfolio_id' => $portfolio->id,
                    'asset_id' => $asset->id,
                    'tenant_profile_id' => $tenant->id,
                    'submitted_by_user_id' => $owner->id,
                    'resolved_by_user_id' => $resolvedAt ? $owner->id : null,
                    'category' => 'general',
                    'priority' => 'medium',
                    'status' => $resolvedAt ? 'resolved' : 'open',
                    'title' => $title,
                    'description' => $title,
                    'requested_at' => $createdAt,
                    'created_at' => $createdAt,
                    'resolved_at' => $resolvedAt,
                ]);
            }

            $query = ['period' => 'this_month'];

            $this->actingAs($owner)
                ->get(route('reports.index', $query))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('comparison.period.date_from', '2026-07-01')
                    ->where('comparison.period.date_to', '2026-07-15')
                    ->where(
                        'comparison.currencyPositions.0',
                        fn ($position): bool => $position['currency'] === 'SAR'
                            && $this->comparisonMetricMatches($position->toArray(), 'collected', 1500, 1000, 50)
                            && $this->comparisonMetricMatches($position->toArray(), 'expenses', 300, 400, -25)
                            && $this->comparisonMetricMatches($position->toArray(), 'net_position', 1200, 600, 100)
                            && $this->comparisonMetricMatches($position->toArray(), 'collection_health', 75, 50, 25),
                    )
                    ->where(
                        'comparison.serviceMetrics',
                        fn ($metrics): bool => $metrics->contains(
                            fn (array $metric): bool => $metric['key'] === 'maintenance_opened'
                                && (float) $metric['current'] === 2.0
                                && (float) $metric['previous'] === 1.0,
                        ) && $metrics->contains(
                            fn (array $metric): bool => $metric['key'] === 'maintenance_resolved'
                                && (float) $metric['current'] === 1.0
                                && (float) $metric['previous'] === 0.0,
                        ),
                    ));

            $sheet = $this->xlsxWorksheetXml(
                $this->actingAs($owner)->get(route('reports.export', $query)),
            );
            $this->assertStringContainsString('What changed', $sheet);
            $this->assertStringContainsString('Previous comparison period', $sheet);
            $this->assertStringContainsString('2026-07-15', $sheet);

            $word = $this->actingAs($owner)
                ->get(route('reports.statement.word', $query))
                ->assertOk();
            $path = tempnam(sys_get_temp_dir(), 'comparison-statement-');
            $this->assertNotFalse($path);
            file_put_contents($path, $word->streamedContent());
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path));
            $documentXml = (string) $zip->getFromName('word/document.xml');
            $zip->close();
            @unlink($path);

            $this->assertStringContainsString('Period comparison', $documentXml);
            $this->assertStringContainsString('مقارنة الفترات', $documentXml);
            $this->assertStringContainsString('1,500.00 SAR', $documentXml);
            $this->assertStringContainsString('1,000.00 SAR', $documentXml);

            $pdf = $this->actingAs($owner)
                ->get(route('reports.statement.pdf', $query))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
            $this->assertSame('%PDF-', substr($pdf->streamedContent(), 0, 5));
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_reports_and_owner_downloads_keep_different_currencies_separate(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Currency Owner']);
        $sarLease = $this->createLease(
            $portfolio,
            $this->createTenantProfile(
                $portfolio,
                $this->createUserWithRole('tenant', $portfolio),
            ),
            $this->createAsset($portfolio, ['currency' => 'SAR']),
            $owner,
            ['currency' => 'SAR', 'rent_amount' => 1000],
        );
        $usdLease = $this->createLease(
            $portfolio,
            $this->createTenantProfile(
                $portfolio,
                $this->createUserWithRole('tenant', $portfolio),
            ),
            $this->createAsset($portfolio, ['currency' => 'USD']),
            $owner,
            ['currency' => 'USD', 'rent_amount' => 200],
        );

        foreach ([
            [$sarLease, 'SAR-PAYMENT', 1000, 'SAR'],
            [$usdLease, 'USD-PAYMENT', 200, 'USD'],
        ] as [$lease, $reference, $amount, $currency]) {
            Payment::query()->create([
                'portfolio_id' => $portfolio->id,
                'lease_id' => $lease->id,
                'tenant_profile_id' => $lease->tenant_profile_id,
                'recorded_by_user_id' => $owner->id,
                'reference' => $reference,
                'type' => 'rent',
                'method' => 'bank_transfer',
                'status' => 'posted',
                'received_on' => now()->toDateString(),
                'amount' => $amount,
                'currency' => $currency,
            ]);
        }

        foreach ([
            [$sarLease, 'SAR expense', 100, 'SAR'],
            [$usdLease, 'USD expense', 20, 'USD'],
        ] as [$lease, $title, $amount, $currency]) {
            ExpenseEntry::query()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $lease->leaseable_id,
                'created_by_user_id' => $owner->id,
                'category' => 'maintenance',
                'title' => $title,
                'incurred_on' => now()->toDateString(),
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'posted',
            ]);
        }

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.currency', null)
                ->where('summary.currencyCount', 2)
                ->where('summary.revenue', null)
                ->where('summary.expenses', null)
                ->where('summary.net', null)
                ->where(
                    'summary.currencyTotals',
                    fn ($positions): bool => collect($positions)->contains(
                        fn (array $position): bool => $position['currency'] === 'SAR'
                            && (float) $position['revenue'] === 1000.0
                            && (float) $position['expenses'] === 100.0
                            && (float) $position['net'] === 900.0,
                    ) && collect($positions)->contains(
                        fn (array $position): bool => $position['currency'] === 'USD'
                            && (float) $position['revenue'] === 200.0
                            && (float) $position['expenses'] === 20.0
                            && (float) $position['net'] === 180.0,
                    ),
                )
                ->where(
                    'charts.revenueByMonth',
                    fn ($rows): bool => collect($rows)->contains('currency', 'SAR')
                        && collect($rows)->contains('currency', 'USD'),
                )
                ->where(
                    'charts.expenseByCategory',
                    fn ($rows): bool => collect($rows)->contains('currency', 'SAR')
                        && collect($rows)->contains('currency', 'USD'),
                ));

        $sheet = $this->xlsxWorksheetXml(
            $this->actingAs($owner)->get(route('reports.export')),
        );
        $this->assertStringContainsString('Currency Position', $sheet);
        $this->assertStringContainsString('SAR', $sheet);
        $this->assertStringContainsString('USD', $sheet);

        $pdf = $this->actingAs($owner)->get(route('reports.statement.pdf'));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame('%PDF-', substr($pdf->streamedContent(), 0, 5));

        $word = $this->actingAs($owner)->get(route('reports.statement.word'));
        $word->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'currency-statement-test-');
        $this->assertNotFalse($path);
        file_put_contents($path, $word->streamedContent());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('1,000.00 SAR', $documentXml);
        $this->assertStringContainsString('200.00 USD', $documentXml);
        $this->assertStringNotContainsString('1,200.00 SAR', $documentXml);
    }

    /** @param array<string, mixed> $position */
    private function comparisonMetricMatches(
        array $position,
        string $key,
        float $current,
        float $previous,
        float $change,
    ): bool {
        return collect($position['metrics'])->contains(
            fn (array $metric): bool => $metric['key'] === $key
                && (float) $metric['current'] === $current
                && (float) $metric['previous'] === $previous
                && (float) $metric['change'] === $change,
        );
    }

    public function test_report_library_links_keep_the_selected_scope_and_hide_disabled_modules(): void
    {
        $portfolio = $this->createPortfolio([
            'module_settings' => [
                'expenses' => false,
            ],
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'rentable' => false,
            'title_en' => 'Scope Tower',
            'title_ar' => 'برج النطاق',
            'code' => 'SCOPE-TOWER',
        ]);
        $query = [
            'date_from' => '2026-01-01',
            'date_to' => '2026-06-30',
            'portfolio_id' => $portfolio->id,
            'property_id' => $property->id,
        ];

        $this->actingAs($owner)
            ->get(route('reports.index', $query))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('reportLibrary', 4)
                ->has('reportLibrary.1.cards', 2)
                ->where('reportLibrary.0.cards.1.key', 'property-operating-report')
                ->has('reportLibrary.0.cards.1.downloads', 3)
                ->where('reportLibrary.0.cards.1.downloads.0.label', 'Download PDF')
                ->where('reportLibrary.0.cards.1.downloads.1.label', 'Download DOCX')
                ->where('reportLibrary.0.cards.1.downloads.2.label', 'Download XLSX')
                ->where('reportLibrary.0.cards.1.scope', [
                    [
                        'label' => 'Selected period',
                        'value' => 'Custom dates · 1 Jan 2026 – 30 Jun 2026',
                    ],
                    [
                        'label' => 'Portfolio scope',
                        'value' => $portfolio->name_en,
                    ],
                    [
                        'label' => 'Property scope',
                        'value' => 'Scope Tower · SCOPE-TOWER',
                    ],
                ])
                ->where(
                    'reportLibrary.0.cards.1.openHref',
                    fn (string $href): bool => str_contains(
                        $href,
                        '/reports/properties/'.$property->id,
                    ),
                )
                ->where('reportLibrary.1.cards.0.key', 'rent-collection')
                ->where(
                    'reportLibrary.1.cards.0.downloads.0.href',
                    fn (string $href): bool => str_contains($href, 'date_from=2026-01-01')
                        && str_contains($href, 'date_to=2026-06-30')
                        && str_contains($href, 'portfolio_id='.$portfolio->id)
                        && str_contains($href, 'property_id='.$property->id),
                )
                ->where(
                    'reportLibrary.1.cards',
                    fn ($cards): bool => collect($cards)->doesntContain(
                        fn (array $card): bool => $card['key'] === 'expenses',
                    ),
                )
                ->where(
                    'reportLibrary.2.cards',
                    fn ($cards): bool => collect(
                        collect($cards)->firstWhere('key', 'occupancy')['scope'],
                    )->keyBy('label')->get('Portfolio scope')['value'] === $portfolio->name_en
                        && collect(
                            collect($cards)->firstWhere('key', 'occupancy')['scope'],
                        )->keyBy('label')->get('Property scope')['value'] === 'Scope Tower · SCOPE-TOWER',
                )
                ->where(
                    'reportLibrary.3.cards',
                    fn ($cards): bool => collect($cards)->doesntContain(
                        fn (array $card): bool => $card['key'] === 'audit',
                    ),
                ));
    }

    public function test_report_library_scope_values_are_fully_localized_in_arabic(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'Arabic Portfolio',
            'name_ar' => 'محفظة التقارير',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'rentable' => false,
            'title_en' => 'Arabic Scope Tower',
            'title_ar' => 'برج التقارير',
            'code' => 'AR-SCOPE',
        ]);

        $this->actingAs($owner)
            ->get(route('reports.index', [
                'locale' => 'ar',
                'period' => 'custom',
                'date_from' => '2026-01-01',
                'date_to' => '2026-06-30',
                'property_id' => $property->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('reportLibrary.0.cards.0.scope', [
                    [
                        'label' => 'الفترة المحددة',
                        'value' => 'تواريخ مخصصة · 1 يناير 2026 – 30 يونيو 2026',
                    ],
                    [
                        'label' => 'نطاق المحفظة',
                        'value' => 'محفظة التقارير',
                    ],
                    [
                        'label' => 'نطاق العقار',
                        'value' => 'برج التقارير · AR-SCOPE',
                    ],
                ]));
    }

    public function test_selected_property_resolves_its_portfolio_for_superadmin_scope(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'Resolved Client Portfolio',
        ]);
        $superadmin = $this->createUserWithRole('superadmin');
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'rentable' => false,
            'title_en' => 'Resolved Property',
            'code' => 'RESOLVED-001',
        ]);

        $this->actingAs($superadmin)
            ->get(route('reports.index', [
                'period' => 'this_month',
                'property_id' => $property->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where(
                    'reportLibrary.0.cards.0.scope',
                    fn ($scope): bool => collect($scope)
                        ->keyBy('label')
                        ->get('Portfolio scope')['value'] === 'Resolved Client Portfolio',
                ));
    }

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

    public function test_owner_statement_is_scoped_and_downloads_real_pdf_word_and_workbook_files(): void
    {
        $portfolio = $this->createPortfolio(['name_en' => 'Own Portfolio', 'name_ar' => 'محفظتي']);
        $foreignPortfolio = $this->createPortfolio(['name_en' => 'Foreign Portfolio']);
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Statement Owner']);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $this->createUserWithRole('tenant', $portfolio, ['name' => 'Own Tenant'])),
            $this->createAsset($portfolio, ['title_en' => 'Own Statement Unit', 'occupancy_status' => 'occupied']),
            $owner,
        );
        $foreignLease = $this->createLease(
            $foreignPortfolio,
            $this->createTenantProfile($foreignPortfolio, $this->createUserWithRole('tenant', $foreignPortfolio, ['name' => 'Foreign Tenant'])),
            $this->createAsset($foreignPortfolio, ['title_en' => 'Foreign Statement Unit', 'occupancy_status' => 'occupied']),
            $foreignOwner,
        );

        foreach ([[$lease, $owner, 'OWN-STMT', 1250], [$foreignLease, $foreignOwner, 'FOREIGN-STMT', 9000]] as [$item, $recorder, $reference, $amount]) {
            Payment::query()->create([
                'portfolio_id' => $item->portfolio_id,
                'lease_id' => $item->id,
                'tenant_profile_id' => $item->tenant_profile_id,
                'recorded_by_user_id' => $recorder->id,
                'reference' => $reference,
                'type' => 'rent',
                'method' => 'cash',
                'status' => 'posted',
                'received_on' => now()->toDateString(),
                'amount' => $amount,
                'currency' => 'SAR',
            ]);
        }

        $this->actingAs($owner)
            ->get(route('reports.statement'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/statement')
                ->where('statement.portfolio.en', 'Own Portfolio')
                ->where('statement.portfolio.ar', 'محفظتي')
                ->where('statement.prepared_for', 'Statement Owner')
                ->where('summary.revenue', fn (int|float $value) => (float) $value === 1250.0)
                ->has('recentPayments', 1)
                ->where('recentPayments.0.reference', 'OWN-STMT'));

        $pdf = $this->actingAs($owner)->get(route('reports.statement.pdf'));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', (string) $pdf->headers->get('content-disposition'));
        $this->assertSame('%PDF-', substr($pdf->streamedContent(), 0, 5));

        $word = $this->actingAs($owner)->get(route('reports.statement.word'));
        $word->assertOk()->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );
        $this->assertStringContainsString('.docx', (string) $word->headers->get('content-disposition'));
        $content = $word->streamedContent();
        $this->assertSame('PK', substr($content, 0, 2));

        $path = tempnam(sys_get_temp_dir(), 'owner-statement-test-');
        $this->assertNotFalse($path);
        file_put_contents($path, $content);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('OWN-STMT', $documentXml);
        $this->assertStringContainsString('كشف المالك', $documentXml);
        $this->assertStringNotContainsString('FOREIGN-STMT', $documentXml);
        $this->assertStringNotContainsString('Foreign Tenant', $documentXml);

        $workbook = $this->actingAs($owner)
            ->get(route('reports.statement.workbook'))
            ->assertOk();
        $this->assertStringContainsString(
            'owner-statement-',
            (string) $workbook->headers->get('content-disposition'),
        );
        $worksheet = $this->xlsxWorksheetXml($workbook);
        $this->assertStringContainsString('OWN-STMT', $worksheet);
        $this->assertStringContainsString('Own Tenant', $worksheet);
        $this->assertStringNotContainsString('FOREIGN-STMT', $worksheet);
        $this->assertStringNotContainsString('Foreign Tenant', $worksheet);
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

    public function test_operational_journal_combines_each_scoped_business_event_and_exports_it(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Journal Owner']);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Journal Unit']);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Journal Tenant']),
        );
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner, [
            'code' => 'JOURNAL-LEASE',
        ]);

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'JOURNAL-PAYMENT',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => today(),
            'amount' => 1500,
            'currency' => 'SAR',
        ]);
        ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'created_by_user_id' => $owner->id,
            'category' => 'maintenance',
            'title' => 'Journal repair',
            'incurred_on' => today(),
            'amount' => 275,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);
        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $owner->id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'Journal open request',
            'description' => 'Open service event',
            'requested_at' => now(),
        ]);
        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $owner->id,
            'resolved_by_user_id' => $owner->id,
            'category' => 'electrical',
            'priority' => 'medium',
            'status' => 'resolved',
            'title' => 'Journal resolved request',
            'description' => 'Resolved service event',
            'requested_at' => now()->subMonth(),
            'resolved_at' => now(),
            'created_at' => now()->subMonth(),
            'updated_at' => now(),
        ]);
        Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'lease_contract',
            'title_en' => 'Journal contract',
            'title_ar' => 'عقد السجل',
            'disk' => 'local',
            'file_path' => 'tests/journal-contract.pdf',
            'original_name' => 'journal-contract.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 128,
            'is_public' => true,
        ]);
        foreach (range(1, 7) as $number) {
            Document::query()->create([
                'portfolio_id' => $portfolio->id,
                'uploaded_by_user_id' => $owner->id,
                'documentable_type' => $lease->getMorphClass(),
                'documentable_id' => $lease->id,
                'type' => 'other',
                'title_en' => "Journal supplement {$number}",
                'title_ar' => "ملحق السجل {$number}",
                'disk' => 'local',
                'file_path' => "tests/journal-supplement-{$number}.pdf",
                'original_name' => "journal-supplement-{$number}.pdf",
                'mime_type' => 'application/pdf',
                'file_size' => 64,
                'is_public' => false,
                'created_at' => today(),
                'updated_at' => today(),
            ]);
        }

        $filters = [
            'date_from' => today()->toDateString(),
            'date_to' => today()->toDateString(),
        ];
        $expectedTypes = [
            'document',
            'expense',
            'lease',
            'maintenance_opened',
            'maintenance_resolved',
            'payment',
        ];

        $this->actingAs($owner)
            ->get(route('reports.index', $filters))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('journalSummary.totalEvents', 13)
                ->where('journalSummary.newLeases', 1)
                ->where('journalSummary.serviceOpened', 1)
                ->where('journalSummary.serviceResolved', 1)
                ->where('journalSummary.documentsAdded', 8)
                ->has('operationalJournal', 12)
                ->where(
                    'operationalJournal',
                    fn ($events): bool => collect($events)
                        ->pluck('type')
                        ->unique()
                        ->sort()
                        ->values()
                        ->all() === $expectedTypes
                        && ! collect($events)->contains('title', 'Journal supplement 7'),
                ));

        $sheet = $this->xlsxWorksheetXml(
            $this->actingAs($owner)->get(route('reports.export', $filters)),
        );
        foreach (['Operational journal', 'JOURNAL-PAYMENT', 'Journal repair', 'Journal contract', 'Journal supplement 7'] as $value) {
            $this->assertStringContainsString($value, $sheet);
        }

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('reports.index', $filters))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('operationalJournal.0.type_label', fn (string $label): bool => $label !== '')
                ->where(
                    'operationalJournal',
                    fn ($events): bool => collect($events)
                        ->pluck('type_label')
                        ->contains('ترحيل دفعة'),
                ));
    }

    public function test_property_filter_scopes_every_report_dataset_to_the_selected_asset_tree(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $root = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Selected Tower',
            'rentable' => false,
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $root->id,
            'title_en' => 'Selected Unit',
            'occupancy_status' => 'occupied',
        ]);
        $otherRoot = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Other Tower',
            'rentable' => false,
        ]);
        $otherUnit = $this->createAsset($portfolio, [
            'parent_id' => $otherRoot->id,
            'title_en' => 'Other Unit',
            'occupancy_status' => 'occupied',
        ]);
        $foreignRoot = $this->createAsset($foreignPortfolio, [
            'asset_type' => 'building',
            'rentable' => false,
        ]);
        $selectedLease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $this->createUserWithRole('tenant', $portfolio)),
            $unit,
            $owner,
        );
        $otherLease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $this->createUserWithRole('tenant', $portfolio)),
            $otherUnit,
            $owner,
        );

        foreach ([[$selectedLease, 1100, 'SELECTED'], [$otherLease, 9200, 'OTHER']] as [$lease, $amount, $reference]) {
            Payment::query()->create([
                'portfolio_id' => $portfolio->id,
                'lease_id' => $lease->id,
                'tenant_profile_id' => $lease->tenant_profile_id,
                'recorded_by_user_id' => $owner->id,
                'reference' => $reference,
                'type' => 'rent',
                'method' => 'cash',
                'status' => 'posted',
                'received_on' => now()->toDateString(),
                'amount' => $amount,
                'currency' => 'SAR',
            ]);
        }

        foreach ([[$unit, 100, 'Selected repair'], [$otherUnit, 800, 'Other repair']] as [$asset, $amount, $title]) {
            ExpenseEntry::query()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
                'created_by_user_id' => $owner->id,
                'category' => 'general',
                'title' => $title,
                'incurred_on' => now()->toDateString(),
                'amount' => $amount,
                'currency' => 'SAR',
                'status' => 'posted',
            ]);
        }

        foreach ([
            [$unit, $selectedLease, 'Selected issue'],
            [$otherUnit, $otherLease, 'Other issue'],
        ] as [$asset, $lease, $title]) {
            MaintenanceRequest::query()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
                'tenant_profile_id' => $lease->tenant_profile_id,
                'submitted_by_user_id' => $owner->id,
                'category' => 'general',
                'priority' => 'medium',
                'status' => 'open',
                'title' => $title,
                'description' => $title,
                'requested_at' => now(),
            ]);
        }

        $this->actingAs($owner)
            ->get(route('reports.index', ['property_id' => $root->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.property_id', $root->id)
                ->has('propertyOptions', 2)
                ->where('summary.revenue', fn (int|float $value) => (float) $value === 1100.0)
                ->where('summary.expenses', fn (int|float $value) => (float) $value === 100.0)
                ->where('summary.openRequests', 1)
                ->where('recentPayments.0.reference', 'SELECTED')
                ->where('maintenanceBacklog.0.title', 'Selected issue')
                ->where(
                    'operationalJournal',
                    fn ($events): bool => collect($events)->contains('title', 'SELECTED')
                        && collect($events)->contains('title', 'Selected repair')
                        && ! collect($events)->contains('title', 'OTHER')
                        && ! collect($events)->contains('title', 'Other repair'),
                ));

        $sheet = $this->xlsxWorksheetXml(
            $this->actingAs($owner)->get(route('reports.export', ['property_id' => $root->id])),
        );
        $this->assertStringContainsString('Selected issue', $sheet);
        $this->assertStringContainsString('Operational journal', $sheet);
        $this->assertStringContainsString('SELECTED', $sheet);
        $this->assertStringContainsString('Selected repair', $sheet);
        $this->assertStringNotContainsString('Other issue', $sheet);
        $this->assertStringNotContainsString('Other repair', $sheet);
        $this->assertStringNotContainsString('9200', $sheet);

        $this->actingAs($owner)
            ->get(route('reports.index', ['property_id' => $unit->id]))
            ->assertForbidden();
        $this->actingAs($owner)
            ->get(route('reports.index', ['property_id' => $foreignRoot->id]))
            ->assertForbidden();
        $this->actingAs($owner)
            ->get(route('reports.statement', ['property_id' => $unit->id]))
            ->assertForbidden();
        $this->actingAs($owner)
            ->get(route('reports.statement.pdf', ['property_id' => $foreignRoot->id]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('reports.presets.store'), [
                'title_en' => 'Selected tower',
                'title_ar' => 'البرج المحدد',
                'visibility' => 'portfolio',
                'filters_json' => ['property_id' => $root->id],
            ])
            ->assertRedirect();
        $this->assertSame(
            $root->id,
            ReportPreset::query()->latest('id')->firstOrFail()->filters_json['property_id'],
        );
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

        $this->actingAs($tenant)
            ->get(route('reports.statement'))
            ->assertForbidden();

        $this->actingAs($tenant)
            ->get(route('reports.statement.pdf'))
            ->assertForbidden();

        $this->actingAs($tenant)
            ->get(route('reports.statement.word'))
            ->assertForbidden();

        $this->actingAs($tenant)
            ->get(route('reports.statement.workbook'))
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
            ->get(route('reports.saved.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/saved')
                ->has('savedPresets', 1)
                ->where('savedPresets.0.title_en', 'Arrears watch')
                ->where('savedPresets.0.period', 'custom')
                ->where('savedPresets.0.date_from', '2026-01-01')
                ->where('savedPresets.0.date_to', '2026-01-31')
                ->where(
                    'savedPresets.0.export_url',
                    fn (string $url): bool => str_contains($url, '/reports/export')
                        && str_contains($url, 'date_from=2026-01-01')
                        && str_contains($url, 'date_to=2026-01-31'),
                ));

        $this->actingAs($owner)
            ->delete(route('reports.presets.destroy', $preset))
            ->assertRedirect();

        $this->assertDatabaseMissing('report_presets', ['id' => $preset->id]);
    }

    public function test_rolling_report_presets_follow_the_calendar_and_keep_the_property_scope(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Calendar Tower',
            'title_ar' => 'برج التقويم',
            'rentable' => false,
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-08-15 12:00:00'));

        $this->actingAs($owner)
            ->post(route('reports.presets.store'), [
                'title_en' => 'Current month',
                'title_ar' => 'الشهر الحالي',
                'visibility' => 'private',
                'is_default' => true,
                'filters_json' => [
                    'period' => 'this_month',
                    'date_from' => '2020-01-01',
                    'date_to' => '2020-01-31',
                    'property_id' => $property->id,
                ],
            ])
            ->assertRedirect();

        $preset = ReportPreset::query()->firstOrFail();

        $this->assertTrue($preset->is_default);
        $this->assertSame([
            'period' => 'this_month',
            'property_id' => $property->id,
        ], $preset->filters_json);

        $this->actingAs($owner)
            ->get(route('reports.index', [
                'period' => 'this_month',
                'property_id' => $property->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.period', 'this_month')
                ->where('filters.date_from', '2026-08-01')
                ->where('filters.date_to', '2026-08-15'));

        $this->actingAs($owner)
            ->get(route('reports.saved.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/saved')
                ->where('savedPresets.0.period', 'this_month')
                ->where('savedPresets.0.date_from', '2026-08-01')
                ->where('savedPresets.0.date_to', '2026-08-15')
                ->where(
                    'savedPresets.0.scope_label',
                    fn (string $label): bool => str_contains($label, 'Calendar Tower')
                        && str_contains($label, $property->code),
                )
                ->where(
                    'savedPresets.0.url',
                    fn (string $url): bool => str_contains($url, 'period=this_month')
                        && str_contains($url, 'property_id='.$property->id)
                        && ! str_contains($url, 'date_from='),
                )
                ->where(
                    'savedPresets.0.export_url',
                    fn (string $url): bool => str_contains($url, '/reports/export')
                        && str_contains($url, 'period=this_month')
                        && str_contains($url, 'property_id='.$property->id)
                        && ! str_contains($url, 'date_from='),
                ));

        $this->travelTo(CarbonImmutable::parse('2026-09-10 12:00:00'));

        $this->actingAs($owner)
            ->get(route('reports.index', [
                'period' => 'this_month',
                'property_id' => $property->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.date_from', '2026-09-01')
                ->where('filters.date_to', '2026-09-10'));

        $this->actingAs($owner)
            ->get(route('reports.saved.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('savedPresets.0.date_from', '2026-09-01')
                ->where('savedPresets.0.date_to', '2026-09-10'));

        $sheet = $this->xlsxWorksheetXml(
            $this->actingAs($owner)->get(route('reports.export', [
                'period' => 'this_month',
                'property_id' => $property->id,
            ])),
        );
        $this->assertStringContainsString('Calendar Tower', $sheet);

        $this->travelBack();
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
            ->get(route('reports.saved.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/saved')
                ->has('savedPresets', 1)
                ->where('savedPresets.0.title_en', 'Global finance view')
                ->where('savedPresets.0.period', 'custom')
                ->where('savedPresets.0.date_from', '2026-01-01')
                ->where('savedPresets.0.date_to', now()->toDateString())
                ->where('savedPresets.0.can_delete', false));
    }

    public function test_saved_reports_have_dedicated_create_edit_duplicate_and_index_pages(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $property = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => 'Focused Tower',
            'title_ar' => 'البرج المركز',
            'rentable' => false,
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-08-20 12:00:00'));

        $this->actingAs($owner)
            ->get(route('reports.saved.create', [
                'period' => 'this_month',
                'property_id' => $property->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/saved-form')
                ->where('mode', 'create')
                ->where('preset', null)
                ->where('filters.period', 'this_month')
                ->where('filters.date_from', '2026-08-01')
                ->where('filters.date_to', '2026-08-20')
                ->where('filters.property_id', $property->id)
                ->where('visibilityOptions', ['private', 'portfolio']));

        $this->actingAs($owner)
            ->post(route('reports.saved.store'), [
                'title_en' => 'Monthly owner review',
                'title_ar' => 'مراجعة المالك الشهرية',
                'visibility' => 'portfolio',
                'is_default' => false,
                'filters_json' => [
                    'period' => 'this_month',
                    'property_id' => $property->id,
                ],
            ])
            ->assertRedirect(route('reports.saved.index'));

        $preset = ReportPreset::query()->firstOrFail();

        $this->actingAs($owner)
            ->get(route('reports.saved.edit', $preset))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/saved-form')
                ->where('mode', 'edit')
                ->where('preset.id', $preset->id)
                ->where('preset.title_en', 'Monthly owner review')
                ->where('filters.period', 'this_month')
                ->where('filters.property_id', $property->id));

        $this->actingAs($owner)
            ->put(route('reports.saved.update', $preset), [
                'title_en' => '30-day owner review',
                'title_ar' => 'مراجعة المالك لثلاثين يوماً',
                'visibility' => 'private',
                'is_default' => true,
                'filters_json' => [
                    'period' => 'last_30_days',
                    'property_id' => $property->id,
                ],
            ])
            ->assertRedirect(route('reports.saved.index'));

        $this->assertDatabaseHas('report_presets', [
            'id' => $preset->id,
            'title_en' => '30-day owner review',
            'visibility' => 'private',
            'is_default' => true,
        ]);
        $this->assertSame(
            [
                'period' => 'last_30_days',
                'property_id' => $property->id,
            ],
            $preset->refresh()->filters_json,
        );

        $this->actingAs($owner)
            ->post(route('reports.saved.duplicate', $preset))
            ->assertRedirect(route('reports.saved.index'));

        $duplicate = ReportPreset::query()->whereKeyNot($preset->id)->firstOrFail();
        $this->assertSame($owner->id, $duplicate->user_id);
        $this->assertSame('private', $duplicate->visibility);
        $this->assertFalse($duplicate->is_default);
        $this->assertSame('30-day owner review copy', $duplicate->title_en);
        $this->assertSame($preset->filters_json, $duplicate->filters_json);

        $this->actingAs($owner)
            ->get(route('reports.saved.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/saved')
                ->has('savedPresets', 2)
                ->where('savedPresets.0.can_edit', true)
                ->where('savedPresets.0.can_duplicate', true)
                ->where('savedPresets.0.edit_url', '/reports/saved/'.$duplicate->id.'/edit'));

        $this->actingAs($tenant)->get(route('reports.saved.index'))->assertForbidden();
        $this->actingAs($tenant)->get(route('reports.saved.create'))->assertForbidden();
        $this->actingAs($tenant)
            ->post(route('reports.saved.duplicate', $preset))
            ->assertForbidden();

        $this->travelBack();
    }

    public function test_saved_report_management_respects_creator_team_and_foreign_boundaries(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);

        $this->actingAs($manager)
            ->post(route('reports.saved.store'), [
                'title_en' => 'Team collections',
                'title_ar' => 'تحصيل الفريق',
                'visibility' => 'portfolio',
                'filters_json' => ['period' => 'this_month'],
            ])
            ->assertRedirect(route('reports.saved.index'));

        $preset = ReportPreset::query()->firstOrFail();

        $this->actingAs($owner)
            ->get(route('reports.saved.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('savedPresets.0.can_edit', false)
                ->where('savedPresets.0.can_duplicate', true)
                ->where('savedPresets.0.can_delete', true));

        $this->actingAs($owner)
            ->get(route('reports.saved.edit', $preset))
            ->assertForbidden();
        $this->actingAs($owner)
            ->put(route('reports.saved.update', $preset), [
                'title_en' => 'Changed',
                'title_ar' => 'معدل',
                'visibility' => 'portfolio',
                'filters_json' => [],
            ])
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('reports.saved.duplicate', $preset))
            ->assertRedirect(route('reports.saved.index'));
        $this->assertDatabaseHas('report_presets', [
            'user_id' => $owner->id,
            'title_en' => 'Team collections copy',
            'visibility' => 'private',
        ]);

        $this->actingAs($foreignOwner)
            ->post(route('reports.saved.duplicate', $preset))
            ->assertNotFound();
        $this->actingAs($foreignOwner)
            ->get(route('reports.saved.edit', $preset))
            ->assertForbidden();
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
            ->get(route('reports.index', ['period' => 'never']))
            ->assertRedirect()
            ->assertSessionHasErrors('period');

        $this->actingAs($owner)
            ->post(route('reports.presets.store'), [
                'title_en' => 'Invalid period',
                'title_ar' => 'فترة غير صالحة',
                'visibility' => 'private',
                'filters_json' => ['period' => 'never'],
            ])
            ->assertSessionHasErrors('filters_json.period');

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
