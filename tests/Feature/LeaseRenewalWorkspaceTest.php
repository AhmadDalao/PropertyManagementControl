<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

final class LeaseRenewalWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-07-25 10:00:00');
    }

    public function test_owner_sees_a_scoped_renewal_queue_with_property_and_financial_context(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $property = $this->property($portfolio, 'Owner Tower');
        $foreignProperty = $this->property($foreignPortfolio, 'Foreign Tower');

        $attention = $this->leaseAtProperty(
            $portfolio,
            $owner,
            $property,
            'Attention Tenant',
            'ATTENTION-LEASE',
            ['ends_at' => today()->addDays(20), 'renewal_notice_days' => 30],
        );
        $upcoming = $this->leaseAtProperty(
            $portfolio,
            $owner,
            $property,
            'Upcoming Tenant',
            'UPCOMING-LEASE',
            ['ends_at' => today()->addDays(70), 'renewal_notice_days' => 30],
        );
        $expired = $this->leaseAtProperty(
            $portfolio,
            $owner,
            $property,
            'Expired Tenant',
            'EXPIRED-LEASE',
            ['status' => 'expired', 'ends_at' => today()->subDays(10)],
        );
        $prepared = $this->leaseAtProperty(
            $portfolio,
            $owner,
            $property,
            'Prepared Tenant',
            'PREPARED-LEASE',
            ['ends_at' => today()->addDays(15), 'renewal_notice_days' => 30],
        );
        $this->createLease(
            $portfolio,
            $prepared->tenantProfile,
            $prepared->leaseable,
            $owner,
            [
                'code' => 'PREPARED-RENEWAL',
                'status' => 'draft',
                'renewed_from_lease_id' => $prepared->id,
                'started_at' => $prepared->ends_at->copy()->addDay(),
                'ends_at' => $prepared->ends_at->copy()->addYear(),
            ],
            syncInstallments: false,
        );
        $this->leaseAtProperty(
            $foreignPortfolio,
            $foreignOwner,
            $foreignProperty,
            'Foreign Tenant',
            'FOREIGN-LEASE',
            ['ends_at' => today()->addDays(10), 'renewal_notice_days' => 30],
        );

        LeaseInstallment::query()->create([
            'lease_id' => $attention->id,
            'sequence' => 1,
            'line_type' => 'rent',
            'label' => 'Outstanding renewal rent',
            'due_date' => today()->subDays(2),
            'amount_due' => 1000,
            'amount_paid' => 200,
            'status' => 'partial',
        ]);

        $this->actingAs($owner)
            ->get(route('lease-renewals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/lease-renewals/index')
                ->where('filters.queue', 'attention')
                ->where('filters.horizon', '90')
                ->where('renewals.total', 2)
                ->where('renewals.data', function ($rows) use ($attention, $expired, $property): bool {
                    $rows = collect($rows);
                    $attentionRow = $rows->firstWhere('id', $attention->id);

                    return $rows->pluck('id')->sort()->values()->all()
                            === collect([$attention->id, $expired->id])->sort()->values()->all()
                        && data_get($attentionRow, 'property.id') === $property->id
                        && data_get($attentionRow, 'renewal_state') === 'attention'
                        && (float) data_get($attentionRow, 'outstanding_amount') === 800.0
                        && data_get($attentionRow, 'overdue_installments_count') === 1;
                })
                ->where('renewalInsights.action_required', 2)
                ->where('renewalInsights.ending_30_days', 2)
                ->where('renewalInsights.renewals_prepared', 1)
                ->where('renewalInsights.expired_unresolved', 1)
                ->where('counts', fn ($counts): bool => collect($counts)
                    ->firstWhere('filter.queue', 'prepared')['value'] === 1)
                ->where('propertyOptions.0.id', $property->id)
                ->where('downloads.pdf', '/lease-renewals/report.pdf')
                ->where('downloads.docx', '/lease-renewals/report.docx')
                ->where('downloads.xlsx', '/exports/lease-renewals'));

        $this->assertNotNull($upcoming);
    }

    public function test_property_filter_search_pagination_and_xlsx_export_share_the_same_scope(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $north = $this->property($portfolio, 'North Tower');
        $south = $this->property($portfolio, 'South Tower');
        $foreign = $this->property($foreignPortfolio, 'Foreign Tower');

        foreach (range(1, 12) as $sequence) {
            $this->leaseAtProperty(
                $portfolio,
                $owner,
                $north,
                "North Tenant {$sequence}",
                sprintf('NORTH-RENEW-%02d', $sequence),
                ['ends_at' => today()->addDays(20 + $sequence)],
            );
        }

        $this->leaseAtProperty(
            $portfolio,
            $owner,
            $south,
            'South Tenant',
            'SOUTH-RENEW-ONLY',
            ['ends_at' => today()->addDays(25)],
        );
        $this->leaseAtProperty(
            $foreignPortfolio,
            $foreignOwner,
            $foreign,
            'Foreign Tenant',
            'FOREIGN-RENEW-ONLY',
            ['ends_at' => today()->addDays(25)],
        );

        $filters = [
            'queue' => 'all',
            'horizon' => '90',
            'property_id' => $north->id,
            'search' => 'NORTH-RENEW',
            'per_page' => 10,
        ];

        $this->actingAs($owner)
            ->get(route('lease-renewals.index', $filters))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('renewals.total', 12)
                ->has('renewals.data', 10)
                ->where('filters.property_id', (string) $north->id)
                ->where('filters.search', 'NORTH-RENEW')
                ->where('renewals.data.0.property.id', $north->id));

        $export = $this->actingAs($owner)
            ->get(route('exports.resource', [
                'resource' => 'lease-renewals',
                ...$filters,
                'per_page' => 100,
            ]))
            ->assertOk();
        $worksheet = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('NORTH-RENEW-01', $worksheet);
        $this->assertStringNotContainsString('SOUTH-RENEW-ONLY', $worksheet);
        $this->assertStringNotContainsString('FOREIGN-RENEW-ONLY', $worksheet);

        $this->actingAs($owner)
            ->get(route('lease-renewals.index', ['property_id' => $foreign->id]))
            ->assertForbidden();
    }

    public function test_arabic_renewal_workspace_uses_rtl_copy_and_localized_property_titles(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $property = $this->property($portfolio, 'Arabic Tower', 'برج التجديد');
        $lease = $this->leaseAtProperty(
            $portfolio,
            $owner,
            $property,
            'Arabic Renewal Tenant',
            'AR-RENEWAL',
            ['ends_at' => today()->addDays(20), 'renewal_notice_days' => 30],
        );

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('lease-renewals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.lease_renewals', 'تجديد العقود')
                ->where('app.translations.lease_renewals.title', 'تجديد العقود')
                ->where('renewals.data.0.id', $lease->id)
                ->where('renewals.data.0.property.title_ar', 'برج التجديد')
                ->where('counts.0.label', 'الكل ضمن المدة'));
    }

    public function test_report_files_and_library_card_use_the_exact_current_renewal_scope(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $property = $this->property($portfolio, 'Report Tower');
        $foreignProperty = $this->property($foreignPortfolio, 'Foreign Report Tower');
        $lease = $this->leaseAtProperty(
            $portfolio,
            $owner,
            $property,
            'Report Tenant',
            'REPORT-RENEWAL',
            ['ends_at' => today()->addDays(20)],
        );
        $this->leaseAtProperty(
            $foreignPortfolio,
            $foreignOwner,
            $foreignProperty,
            'Foreign Report Tenant',
            'FOREIGN-REPORT-RENEWAL',
            ['ends_at' => today()->addDays(20)],
        );
        LeaseInstallment::query()->create([
            'lease_id' => $lease->id,
            'sequence' => 1,
            'line_type' => 'rent',
            'label' => 'Renewal balance',
            'due_date' => today()->subDay(),
            'amount_due' => 1200,
            'amount_paid' => 200,
            'status' => 'partial',
        ]);
        $filters = [
            'queue' => 'all',
            'horizon' => '90',
            'property_id' => $property->id,
            'search' => 'REPORT-RENEWAL',
        ];

        $pdf = $this->actingAs($owner)
            ->get(route('lease-renewals.report.pdf', $filters))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertSame('%PDF-', substr($pdf->streamedContent(), 0, 5));

        $word = $this->actingAs($owner)
            ->get(route('lease-renewals.report.word', $filters))
            ->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            (string) $word->headers->get('content-type'),
        );
        $this->assertSame('PK', substr($word->streamedContent(), 0, 2));
        $documentXml = $this->docxDocumentXml($word);
        $this->assertStringContainsString('REPORT-RENEWAL', $documentXml);
        $this->assertStringNotContainsString('FOREIGN-REPORT-RENEWAL', $documentXml);
        $this->assertStringContainsString('1000', $documentXml);

        $this->actingAs($owner)
            ->get(route('reports.index', [
                'date_from' => '2026-01-01',
                'date_to' => '2026-01-31',
                'property_id' => $property->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('reportLibrary.2.cards.0.key', 'lease-renewals')
                ->where(
                    'reportLibrary.2.cards.0.openHref',
                    '/lease-renewals?property_id='.$property->id,
                )
                ->has('reportLibrary.2.cards.0.downloads', 3)
                ->where('reportLibrary.2.cards.0.downloads.0.label', 'Download PDF')
                ->where('reportLibrary.2.cards.0.downloads.1.label', 'Download DOCX')
                ->where('reportLibrary.2.cards.0.downloads.2.label', 'Download XLSX')
                ->where('reportLibrary.2.cards.0.scope.0.label', 'Current snapshot'));
    }

    public function test_tenants_and_disabled_portfolios_cannot_open_or_export_renewal_data(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        $this->actingAs($tenant)
            ->get(route('lease-renewals.index'))
            ->assertForbidden();
        $this->actingAs($tenant)
            ->get(route('exports.resource', ['resource' => 'lease-renewals']))
            ->assertForbidden();
        $this->actingAs($tenant)
            ->get(route('lease-renewals.report.pdf'))
            ->assertForbidden();
        $this->actingAs($tenant)
            ->get(route('lease-renewals.report.word'))
            ->assertForbidden();

        $disabledPortfolio = $this->createPortfolio([
            'module_settings' => ['leases' => false],
        ]);
        $owner = $this->createUserWithRole('owner', $disabledPortfolio);

        $this->actingAs($owner)
            ->get(route('lease-renewals.index'))
            ->assertForbidden();
        $this->actingAs($owner)
            ->get(route('exports.resource', ['resource' => 'lease-renewals']))
            ->assertForbidden();
        $this->actingAs($owner)
            ->get(route('lease-renewals.report.pdf'))
            ->assertForbidden();
        $this->actingAs($owner)
            ->get(route('lease-renewals.report.word'))
            ->assertForbidden();
    }

    private function property(
        Portfolio $portfolio,
        string $title,
        ?string $titleAr = null,
    ): Asset {
        return $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'title_en' => $title,
            'title_ar' => $titleAr ?? "عقار {$title}",
            'rentable' => false,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function leaseAtProperty(
        Portfolio $portfolio,
        User $owner,
        Asset $property,
        string $tenantName,
        string $code,
        array $attributes = [],
    ): Lease {
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $property->id,
            'title_en' => "{$code} Unit",
            'title_ar' => "وحدة {$code}",
        ]);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => $tenantName]),
        );

        return $this->createLease(
            $portfolio,
            $tenant,
            $unit,
            $owner,
            ['code' => $code, ...$attributes],
            syncInstallments: false,
        );
    }

    private function docxDocumentXml(TestResponse $response): string
    {
        $path = tempnam(sys_get_temp_dir(), 'renewal-docx-');
        $this->assertNotFalse($path);
        file_put_contents($path, $response->streamedContent());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1);
    }
}
