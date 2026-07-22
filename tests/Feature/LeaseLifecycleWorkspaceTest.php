<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Payment;
use App\Modules\Leases\Actions\ManageLeases;
use App\Modules\Payments\Actions\PaymentAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
        app(PaymentAllocator::class)->allocate($payment);

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
                ->missing('leases.data.0.documents')
                ->missing('leases.data.0.installments'));

        $this->actingAs($owner)
            ->get(route('leases.show', $lease))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('detailPage.related.0.rows', 4)
                ->where('detailPage.documents.0.href', route('documents.download', $document)));
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
                ->missing('leases.data.0.installments'));

        $this->actingAs($owner)
            ->get(route('leases.show', $lease))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.related.0.rows.1.Installment', 'Rent Jan 15-Apr 14 2026')
                ->where('detailPage.related.0.rows.1.Due date', '2026-01-15')
                ->where('detailPage.related.0.rows.2.Installment', 'Rent Apr 15-Jul 14 2026')
                ->where('detailPage.related.0.rows.2.Due date', '2026-04-10'));
    }

    public function test_lease_detail_opens_a_prefilled_pdf_only_signed_contract_upload(): void
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
        $uploadUrl = route('documents.create', [
            'documentable_type' => 'lease',
            'documentable_id' => $lease->id,
            'type' => 'signed_contract',
            'title_en' => "Signed contract {$lease->code}",
            'title_ar' => "العقد الموقع {$lease->code}",
        ]);

        $this->actingAs($owner)
            ->get(route('leases.show', $lease))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.workflow.eyebrow', 'Next step')
                ->where('detailPage.workflow.actions', fn ($actions) => collect($actions)->contains(
                    fn (array $action): bool => $action['label'] === 'Upload signed PDF'
                        && $action['href'] === $uploadUrl
                )));

        $this->actingAs($owner)
            ->get($uploadUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-form')
                ->where(
                    'formPage.initialValues.documentable_type',
                    'lease',
                )
                ->where(
                    'formPage.initialValues.documentable_id',
                    (string) $lease->id,
                )
                ->where(
                    'formPage.initialValues.type',
                    'signed_contract',
                )
                ->where(
                    'formPage.fields',
                    fn ($fields) => collect($fields)->contains(
                        fn (array $field) => ($field['name'] ?? null) === 'file'
                            && ($field['accept'] ?? null) === '.pdf,application/pdf',
                    ),
                ));
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

        $this->actingAs($owner)
            ->post(route('leases.signed-contract', $lease), [
                'signed_contract' => UploadedFile::fake()->create('signed-contract.txt', 64, 'application/pdf'),
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
                'signed_contract' => $this->fakePdf('signed-contract.pdf'),
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

    public function test_tenant_lease_detail_hides_internal_notes_admin_actions_and_internal_documents(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
            ['notes' => 'Internal renewal negotiation.'],
        );
        Storage::disk('local')->put('documents/leases/visible.pdf', '%PDF-visible');
        Storage::disk('local')->put('documents/leases/internal.pdf', '%PDF-internal');
        $visible = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'lease_contract',
            'title_en' => 'Tenant contract',
            'disk' => 'local',
            'file_path' => 'documents/leases/visible.pdf',
            'original_name' => 'visible.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 12,
            'is_public' => true,
        ]);
        Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'owner_report',
            'title_en' => 'Internal owner report',
            'disk' => 'local',
            'file_path' => 'documents/leases/internal.pdf',
            'original_name' => 'internal.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 13,
        ]);

        $this->actingAs($tenantUser)
            ->get(route('leases.show', $lease))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.header.actions', fn ($actions) => collect($actions)->pluck('label')->all() === [
                    'Contract PDF',
                ])
                ->where('detailPage.workflow.actions', fn ($actions) => collect($actions)->pluck('label')->all() === [
                    'Tenant statement',
                ])
                ->where('detailPage.sections.0.items', fn ($items) => ! collect($items)->contains('label', 'Notes')
                    && collect($items)->every(fn ($item) => empty($item['href'])))
                ->where('detailPage.related.1.actionHref', null)
                ->where('detailPage.timeline', [])
                ->has('detailPage.documents', 1)
                ->where('detailPage.documents.0.href', route('documents.download', $visible)));
    }

    public function test_alias_leases_enforce_exclusivity_and_release_occupancy(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $firstTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $secondTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $asset = $this->createAsset($portfolio, ['occupancy_status' => 'occupied']);
        $lease = $this->createLease($portfolio, $firstTenant, $asset, $owner);
        $lease->update(['leaseable_type' => (new Asset)->getMorphClass()]);

        $this->actingAs($owner)
            ->from(route('leases.create'))
            ->post(route('leases.store'), [
                'tenant_profile_id' => $secondTenant->id,
                'asset_id' => $asset->id,
                'status' => 'active',
                'payment_frequency' => 'monthly',
                'started_at' => now()->toDateString(),
                'ends_at' => now()->addYear()->toDateString(),
                'rent_amount' => 2000,
            ])
            ->assertRedirect(route('leases.create'))
            ->assertSessionHasErrors('asset_id');

        $this->actingAs($owner)
            ->delete(route('leases.destroy', $lease))
            ->assertRedirect(route('leases.show', $lease));

        $this->assertSame('terminated', $lease->fresh()->status);
        $this->assertSame('vacant', $asset->fresh()->occupancy_status);
    }

    public function test_non_rentable_assets_cannot_receive_a_lease(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $asset = $this->createAsset($portfolio, ['rentable' => false]);

        $this->actingAs($owner)
            ->from(route('leases.create'))
            ->post(route('leases.store'), [
                'tenant_profile_id' => $tenant->id,
                'asset_id' => $asset->id,
                'status' => 'active',
                'payment_frequency' => 'monthly',
                'started_at' => now()->toDateString(),
                'ends_at' => now()->addYear()->toDateString(),
                'rent_amount' => 2000,
            ])
            ->assertRedirect(route('leases.create'))
            ->assertSessionHasErrors('asset_id');

        $this->assertDatabaseMissing('leases', ['leaseable_id' => $asset->id]);
    }

    public function test_draft_lease_cannot_activate_after_its_asset_becomes_non_rentable(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $asset = $this->createAsset($portfolio);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner, ['status' => 'draft']);
        $asset->update(['rentable' => false]);

        $this->actingAs($owner)
            ->from(route('leases.edit', $lease))
            ->put(route('leases.update', $lease), [
                'status' => 'active',
                'signed_at' => null,
                'notes' => null,
            ])
            ->assertRedirect(route('leases.edit', $lease))
            ->assertSessionHasErrors('asset_id');

        $this->assertSame('draft', $lease->fresh()->status);
        $this->assertNotSame('occupied', $asset->fresh()->occupancy_status);
    }

    public function test_lease_action_rejects_cross_portfolio_mutation_when_reused_directly(): void
    {
        $ownerPortfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $ownerPortfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $foreignTenant = $this->createTenantProfile(
            $foreignPortfolio,
            $this->createUserWithRole('tenant', $foreignPortfolio),
        );
        $foreignLease = $this->createLease(
            $foreignPortfolio,
            $foreignTenant,
            $this->createAsset($foreignPortfolio),
            $foreignOwner,
        );

        try {
            app(ManageLeases::class)->terminate($owner, $foreignLease);
            $this->fail('Cross-portfolio lease mutation was not rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame('active', $foreignLease->fresh()->status);
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
            'is_public' => true,
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

    public function test_generated_contract_statement_and_receipt_are_private_pdf_documents(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio([
            'contact_email' => 'operations@example.test',
            'contact_phone' => '+966500000000',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Portfolio Owner']);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Contract Tenant']);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser, ['national_id' => '1000000000']);
        $asset = $this->createAsset($portfolio, [
            'title_en' => 'Riyadh Apartment 12',
            'title_ar' => 'شقة الرياض 12',
            'address' => 'Riyadh, Saudi Arabia',
            'address_ar' => 'الرياض، المملكة العربية السعودية',
        ]);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner, [
            'terms_json' => [
                'en' => 'Tenant must follow the approved building rules.',
                'ar' => 'يلتزم المستأجر بقواعد المبنى المعتمدة.',
            ],
        ]);
        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'PDF-RECEIPT-1',
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 1000,
            'currency' => 'SAR',
        ]);
        app(PaymentAllocator::class)->allocate($payment);

        $contract = $this->actingAs($owner)->get(route('leases.contract', $lease));
        $statement = $this->actingAs($owner)->get(route('leases.statement', $lease));
        $receipt = $this->actingAs($owner)->get(route('payments.receipt', $payment));

        foreach ([$contract, $statement, $receipt] as $response) {
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertSame('%PDF-', substr($response->streamedContent(), 0, 5));
        }

        foreach (['lease_contract', 'tenant_statement', 'receipt'] as $type) {
            $document = Document::query()->where('type', $type)->firstOrFail();

            $this->assertTrue($document->is_public);
            $this->assertSame('application/pdf', $document->mime_type);
            $this->assertStringEndsWith('.pdf', $document->original_name);
            Storage::disk('local')->assertExists($document->file_path);
            $this->assertSame('%PDF-', substr(Storage::disk('local')->get($document->file_path), 0, 5));
        }

        $contractDocument = Document::query()->where('type', 'lease_contract')->firstOrFail();
        $firstContractPath = $contractDocument->file_path;

        $this->actingAs($owner)
            ->get(route('leases.contract', $lease))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $replacementPath = $contractDocument->fresh()->file_path;
        $this->assertNotSame($firstContractPath, $replacementPath);
        Storage::disk('local')->assertMissing($firstContractPath);
        Storage::disk('local')->assertExists($replacementPath);
    }

    public function test_receipt_generation_rejects_non_posted_payments(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease($portfolio, $tenant, $this->createAsset($portfolio), $owner);
        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'PENDING-NO-RECEIPT',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'pending',
            'received_on' => now()->toDateString(),
            'amount' => 1000,
            'currency' => 'SAR',
        ]);

        $this->actingAs($owner)
            ->get(route('payments.receipt', $payment))
            ->assertUnprocessable();

        $this->assertDatabaseMissing('documents', [
            'documentable_type' => $payment->getMorphClass(),
            'documentable_id' => $payment->id,
            'type' => 'receipt',
        ]);
    }
}
