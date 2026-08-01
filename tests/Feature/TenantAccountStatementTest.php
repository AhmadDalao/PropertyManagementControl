<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TenantAccountStatementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_opens_a_complete_currency_safe_tenant_statement(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'Operating Portfolio',
            'name_ar' => 'محفظة التشغيل',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Portfolio Owner']);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Account Tenant']);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $sarAsset = $this->createAsset($portfolio, ['title_en' => 'SAR Unit', 'title_ar' => 'وحدة ريال']);
        $usdAsset = $this->createAsset($portfolio, ['title_en' => 'USD Unit', 'title_ar' => 'وحدة دولار']);
        $sarLease = $this->createLease($portfolio, $tenant, $sarAsset, $owner, [
            'code' => 'TENANT-SAR-01',
            'currency' => 'SAR',
        ]);
        $usdLease = $this->createLease($portfolio, $tenant, $usdAsset, $owner, [
            'code' => 'TENANT-USD-01',
            'currency' => 'USD',
            'status' => 'expired',
        ]);
        $sarLease->installments()->firstOrFail()->update([
            'amount_due' => 1000,
            'amount_paid' => 400,
            'status' => 'partial',
        ]);
        $usdLease->installments()->firstOrFail()->update([
            'amount_due' => 500,
            'amount_paid' => 100,
            'status' => 'partial',
        ]);
        $sarPayment = $this->payment($portfolio->id, $tenant->id, $sarLease->id, $owner->id, 'SAR-PAY', 400, 'SAR');
        $this->payment($portfolio->id, $tenant->id, $usdLease->id, $owner->id, 'USD-PAY', 100, 'USD');
        MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $sarAsset->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'Statement service request',
            'description' => 'Included in the selected period.',
            'requested_at' => now(),
        ]);
        Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $sarLease::class,
            'documentable_id' => $sarLease->id,
            'type' => 'signed_contract',
            'title_en' => 'Signed contract',
            'title_ar' => 'العقد الموقع',
            'disk' => 'local',
            'file_path' => 'documents/tenant-account/signed.pdf',
            'original_name' => 'signed.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 120,
        ]);

        $this->actingAs($owner)
            ->get(route('tenants.statement.show', [
                'tenant' => $tenant,
                'date_from' => now()->startOfYear()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/tenants/statement')
                ->where('tenant.name', 'Account Tenant')
                ->where('statement.lease_count', 2)
                ->where('statement.active_lease_count', 1)
                ->where('statement.open_maintenance_count', 1)
                ->where('counts.payments', 2)
                ->where('counts.maintenance', 1)
                ->where('counts.documents', 1)
                ->where('leases.0.code', 'TENANT-SAR-01')
                ->where('payments.0.reference', fn (string $reference): bool => in_array($reference, ['SAR-PAY', 'USD-PAY'], true))
                ->where('maintenance.0.title', 'Statement service request')
                ->where('documents.0.title_en', 'Signed contract')
                ->where('statement.financials', function ($financials): bool {
                    $byCurrency = collect($financials)->keyBy('currency');

                    return $byCurrency->keys()->sort()->values()->all() === ['SAR', 'USD']
                        && (float) $byCurrency['SAR']['received'] === 400.0
                        && (float) $byCurrency['USD']['received'] === 100.0
                        && (float) $byCurrency['SAR']['contract_balance'] !==
                            (float) $byCurrency['USD']['contract_balance'];
                }));

        $this->assertSame($tenant->id, $sarPayment->tenant_profile_id);
    }

    public function test_assigned_manager_statement_excludes_unassigned_contracts_and_payments(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Scoped Tenant']),
        );
        $visibleAsset = $this->createAsset($portfolio, ['title_en' => 'Visible Unit']);
        $hiddenAsset = $this->createAsset($portfolio, ['title_en' => 'Hidden Unit']);
        $this->assignManagerToAsset($manager, $visibleAsset);
        $visibleLease = $this->createLease($portfolio, $tenant, $visibleAsset, $manager, [
            'code' => 'VISIBLE-LEASE',
        ]);
        $hiddenLease = $this->createLease($portfolio, $tenant, $hiddenAsset, null, [
            'code' => 'HIDDEN-LEASE',
        ]);
        $this->payment($portfolio->id, $tenant->id, $visibleLease->id, $manager->id, 'VISIBLE-PAY', 200, 'SAR');
        $this->payment($portfolio->id, $tenant->id, $hiddenLease->id, $manager->id, 'HIDDEN-PAY', 900, 'SAR');

        $response = $this->actingAs($manager)
            ->get(route('tenants.statement.show', $tenant));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('statement.lease_count', 1)
                ->where('leases.0.code', 'VISIBLE-LEASE')
                ->where('counts.payments', 1)
                ->where('payments.0.reference', 'VISIBLE-PAY'));
        $response->assertDontSee('HIDDEN-LEASE');
        $response->assertDontSee('HIDDEN-PAY');
    }

    public function test_statement_exports_are_real_pdf_docx_and_xlsx_files(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Export Tenant']),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
            ['code' => 'EXPORT-LEASE'],
        );
        $this->payment($portfolio->id, $tenant->id, $lease->id, $owner->id, 'EXPORT-PAY', 300, 'SAR');

        $pdf = $this->actingAs($owner)->get(route('tenants.statement.pdf', $tenant));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));
        $this->assertSame('%PDF-', substr($pdf->streamedContent(), 0, 5));

        $word = $this->actingAs($owner)->get(route('tenants.statement.word', $tenant));
        $word->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            (string) $word->headers->get('content-type'),
        );
        $this->assertSame('PK', substr($word->streamedContent(), 0, 2));

        $workbook = $this->actingAs($owner)->get(route('tenants.statement.workbook', $tenant));
        $workbook->assertOk();
        $sheet = $this->xlsxWorksheetXml($workbook);
        $this->assertStringContainsString('EXPORT-LEASE', $sheet);
        $this->assertStringContainsString('EXPORT-PAY', $sheet);
    }

    public function test_statement_totals_remain_exact_when_visible_rows_reach_the_safety_limit(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $payments = [];

        foreach (range(1, 105) as $number) {
            $payments[] = [
                'portfolio_id' => $portfolio->id,
                'lease_id' => $lease->id,
                'tenant_profile_id' => $tenant->id,
                'recorded_by_user_id' => $owner->id,
                'reference' => sprintf('LIMIT-%03d', $number),
                'type' => 'rent',
                'method' => 'bank_transfer',
                'status' => 'posted',
                'received_on' => now()->toDateString(),
                'amount' => 10,
                'currency' => 'SAR',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Payment::query()->insert($payments);

        $this->actingAs($owner)
            ->get(route('tenants.statement.show', $tenant))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.payments', 105)
                ->has('payments', 100)
                ->where('statement.financials.0.received', fn (int|float $value): bool => (float) $value === 1050.0));
    }

    public function test_tenant_and_cross_portfolio_users_cannot_open_or_export_statement(): void
    {
        $portfolio = $this->createPortfolio();
        $otherPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $otherPortfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);

        foreach ([
            'tenants.statement.show',
            'tenants.statement.pdf',
            'tenants.statement.word',
            'tenants.statement.workbook',
        ] as $route) {
            $this->actingAs($tenantUser)->get(route($route, $tenant))->assertForbidden();
            $this->actingAs($foreignOwner)->get(route($route, $tenant))->assertForbidden();
        }

        $this->actingAs($owner)
            ->get(route('tenants.statement.show', [
                'tenant' => $tenant,
                'date_from' => '2026-08-20',
                'date_to' => '2026-08-01',
            ]))
            ->assertSessionHasErrors('date_to');
    }

    private function payment(
        int $portfolioId,
        int $tenantId,
        int $leaseId,
        int $actorId,
        string $reference,
        float $amount,
        string $currency,
    ): Payment {
        return Payment::query()->create([
            'portfolio_id' => $portfolioId,
            'lease_id' => $leaseId,
            'tenant_profile_id' => $tenantId,
            'recorded_by_user_id' => $actorId,
            'reference' => $reference,
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }
}
