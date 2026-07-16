<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Payment;
use App\Services\LeaseFinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LeaseLifecycleWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_lease_index_exposes_financial_schedule_and_document_summary(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Schedule Tenant']);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Schedule Unit']);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner, [
            'started_at' => now()->startOfMonth()->toDateString(),
            'ends_at' => now()->startOfMonth()->addMonths(2)->endOfMonth()->toDateString(),
            'rent_amount' => 2000,
            'deposit_amount' => 1000,
        ]);

        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'LEASE-PAY-1',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 2500,
            'currency' => 'SAR',
        ]);
        app(LeaseFinancialService::class)->allocatePayment($payment);

        Storage::disk('local')->put('documents/leases/signed.pdf', 'signed');
        $document = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'signed_contract',
            'title_en' => 'Signed contract',
            'disk' => 'local',
            'file_path' => 'documents/leases/signed.pdf',
            'original_name' => 'signed.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 6,
        ]);

        $this->actingAs($owner)
            ->get(route('leases.index', ['search' => $lease->code]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/leases/index')
                ->where('leases.total', 1)
                ->where('leases.data.0.code', $lease->code)
                ->where('leases.data.0.total_due', 7000)
                ->where('leases.data.0.total_paid', 2500)
                ->where('leases.data.0.balance_remaining', 4500)
                ->where('leases.data.0.installment_count', 4)
                ->where('leases.data.0.open_installment_count', 3)
                ->where('leases.data.0.paid_percent', 35.7)
                ->where('leases.data.0.billing_day', null)
                ->where('leases.data.0.tax_amount', 0)
                ->where('leaseInsights.total', 1)
                ->where('leaseInsights.active', 1)
                ->where('leaseInsights.balance_remaining', 4500)
                ->where('leases.data.0.documents.0.download_url', route('documents.download', $document))
                ->has('leases.data.0.installments', 4));
    }

    public function test_lease_workspace_exposes_frequency_aware_schedule_rows(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Quarterly Tenant']);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Quarterly Unit']);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner, [
            'started_at' => '2026-01-15',
            'ends_at' => '2026-12-31',
            'payment_frequency' => 'quarterly',
            'rent_amount' => 9000,
            'deposit_amount' => 3000,
            'tax_amount' => 500,
            'discount_amount' => 100,
            'billing_day' => 10,
        ]);

        $this->actingAs($owner)
            ->get(route('leases.index', ['search' => $lease->code]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/leases/index')
                ->where('leases.total', 1)
                ->where('leases.data.0.code', $lease->code)
                ->where('leases.data.0.payment_frequency', 'quarterly')
                ->where('leases.data.0.installment_count', 5)
                ->where('leases.data.0.total_due', 40600)
                ->where('leases.data.0.billing_day', 10)
                ->where('leases.data.0.tax_amount', 500)
                ->where('leases.data.0.discount_amount', 100)
                ->where('leases.data.0.installments.1.label', 'Rent Jan 15-Apr 14 2026')
                ->where('leases.data.0.installments.1.due_date', '2026-01-15')
                ->where('leases.data.0.installments.2.label', 'Rent Apr 15-Jul 14 2026')
                ->where('leases.data.0.installments.2.due_date', '2026-04-10'));
    }

    public function test_signed_contract_upload_requires_pdf(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $tenantUser),
            $this->createAsset($portfolio),
            $owner,
        );

        $this->actingAs($owner)
            ->post(route('leases.signed-contract', $lease), [
                'signed_contract' => UploadedFile::fake()->image('signed-contract.png'),
            ])
            ->assertSessionHasErrors('signed_contract');

        $this->actingAs($owner)
            ->post(route('leases.signed-contract', $lease), [
                'signed_contract' => UploadedFile::fake()->create('signed-contract.pdf', 64, 'text/plain'),
            ])
            ->assertSessionHasErrors('signed_contract');

        $this->assertDatabaseMissing('documents', [
            'documentable_id' => $lease->id,
            'type' => 'signed_contract',
        ]);
    }

    public function test_signed_contract_upload_accepts_pdf_and_stores_document(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $tenantUser),
            $this->createAsset($portfolio),
            $owner,
        );

        $this->actingAs($owner)
            ->post(route('leases.signed-contract', $lease), [
                'signed_contract' => UploadedFile::fake()->create('signed-contract.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect(route('leases.show', $lease));

        $document = Document::query()
            ->where('documentable_id', $lease->id)
            ->where('type', 'signed_contract')
            ->firstOrFail();

        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertStringEndsWith('.pdf', $document->original_name);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_tenant_dashboard_exposes_secure_contract_document_and_receipt_links(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease($portfolio, $tenant, $this->createAsset($portfolio), $owner);
        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'TENANT-RECEIPT',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 1000,
            'currency' => 'SAR',
        ]);

        Storage::disk('local')->put('documents/leases/tenant-contract.pdf', 'contract');
        $document = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'lease_contract',
            'title_en' => 'Lease contract',
            'disk' => 'local',
            'file_path' => 'documents/leases/tenant-contract.pdf',
            'original_name' => 'tenant-contract.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 8,
        ]);

        $this->actingAs($tenantUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('tenantPortal.lease.contract_url', route('leases.contract', $lease))
                ->where('tenantPortal.lease.statement_url', route('leases.statement', $lease))
                ->where('tenantPortal.documents.0.download_url', route('documents.download', $document))
                ->where('tenantPortal.payments.0.receipt_url', route('payments.receipt', $payment)));
    }

    public function test_tenant_cannot_access_another_tenants_lease_contract_statement_or_receipt(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $otherTenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $otherTenant = $this->createTenantProfile($portfolio, $otherTenantUser);
        $ownLease = $this->createLease($portfolio, $tenant, $this->createAsset($portfolio), $owner);
        $otherLease = $this->createLease($portfolio, $otherTenant, $this->createAsset($portfolio), $owner);
        $otherPayment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $otherLease->id,
            'tenant_profile_id' => $otherTenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'OTHER-RECEIPT',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 1000,
            'currency' => 'SAR',
        ]);

        $this->actingAs($tenantUser)
            ->get(route('leases.contract', $otherLease))
            ->assertForbidden();

        $this->actingAs($tenantUser)
            ->get(route('leases.statement', $otherLease))
            ->assertForbidden();

        $this->actingAs($tenantUser)
            ->get(route('payments.receipt', $otherPayment))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('leases.contract', $ownLease))
            ->assertOk();
    }
}
