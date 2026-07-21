<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_download_their_own_lease_document(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenantProfile = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $lease = $this->createLease($portfolio, $tenantProfile, $asset, $manager);

        $path = "generated/leases/{$lease->id}/lease-contract.pdf";
        Storage::disk('local')->put($path, 'lease-contract');

        $document = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $manager->id,
            'documentable_type' => Lease::class,
            'documentable_id' => $lease->id,
            'type' => 'lease_contract',
            'title_en' => 'Lease contract',
            'title_ar' => 'عقد الإيجار',
            'disk' => 'local',
            'file_path' => $path,
            'original_name' => 'lease-contract.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 14,
            'is_public' => true,
        ]);

        $this->actingAs($tenantUser)
            ->get(route('documents.download', $document))
            ->assertOk()
            ->assertDownload('lease-contract.pdf');
    }

    public function test_tenant_cannot_download_another_tenants_document(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $otherTenantUser = $this->createUserWithRole('tenant', $portfolio);

        $tenantProfile = $this->createTenantProfile($portfolio, $tenantUser);
        $otherTenantProfile = $this->createTenantProfile($portfolio, $otherTenantUser);

        $asset = $this->createAsset($portfolio);
        $lease = $this->createLease($portfolio, $tenantProfile, $asset, $manager);
        $otherLease = $this->createLease(
            $portfolio,
            $otherTenantProfile,
            $this->createAsset($portfolio),
            $manager
        );

        Storage::disk('local')->put("generated/leases/{$otherLease->id}/lease-contract.pdf", 'lease-contract');

        $document = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $manager->id,
            'documentable_type' => Lease::class,
            'documentable_id' => $otherLease->id,
            'type' => 'lease_contract',
            'title_en' => 'Lease contract',
            'title_ar' => 'عقد الإيجار',
            'disk' => 'local',
            'file_path' => "generated/leases/{$otherLease->id}/lease-contract.pdf",
            'original_name' => 'lease-contract.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 14,
            'is_public' => true,
        ]);

        $this->actingAs($tenantUser)
            ->get(route('documents.download', $document))
            ->assertForbidden();
    }

    public function test_tenant_cannot_download_hidden_or_internal_documents_from_their_own_lease(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $manager,
        );
        Storage::disk('local')->put('documents/hidden.pdf', '%PDF-hidden');
        Storage::disk('local')->put('documents/internal.pdf', '%PDF-internal');
        $hidden = $this->document($portfolio->id, $manager->id, $lease, [
            'type' => 'lease_contract',
            'title_en' => 'Hidden contract',
            'file_path' => 'documents/hidden.pdf',
            'is_public' => false,
        ]);
        $internal = $this->document($portfolio->id, $manager->id, $lease, [
            'type' => 'owner_report',
            'title_en' => 'Owner only report',
            'file_path' => 'documents/internal.pdf',
            'is_public' => true,
        ]);

        $this->actingAs($tenantUser)->get(route('documents.download', $hidden))->assertForbidden();
        $this->actingAs($tenantUser)->get(route('documents.download', $internal))->assertForbidden();
    }

    public function test_tenant_can_download_their_visible_payment_receipt_document(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $manager,
        );
        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $manager->id,
            'reference' => 'DOC-RECEIPT-1',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 500,
            'currency' => 'SAR',
        ]);
        Storage::disk('local')->put('documents/receipt.pdf', '%PDF-receipt');
        $document = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $manager->id,
            'documentable_type' => $payment->getMorphClass(),
            'documentable_id' => $payment->id,
            'type' => 'receipt',
            'title_en' => 'Payment receipt',
            'title_ar' => 'إيصال الدفعة',
            'disk' => 'local',
            'file_path' => 'documents/receipt.pdf',
            'original_name' => 'receipt.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 12,
            'is_public' => true,
        ]);

        $this->actingAs($tenantUser)
            ->get(route('documents.download', $document))
            ->assertOk()
            ->assertDownload('receipt.pdf');
    }

    public function test_authorized_manager_receives_not_found_for_a_missing_private_file(): void
    {
        Storage::fake('local');

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
        $document = $this->document($portfolio->id, $owner->id, $lease, [
            'file_path' => 'documents/missing.pdf',
        ]);

        $this->actingAs($owner)
            ->get(route('documents.download', $document))
            ->assertNotFound();
    }

    /** @param array<string, mixed> $attributes */
    private function document(int $portfolioId, int $ownerId, Lease $lease, array $attributes): Document
    {
        return Document::query()->create(array_merge([
            'portfolio_id' => $portfolioId,
            'uploaded_by_user_id' => $ownerId,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'lease_contract',
            'title_en' => 'Lease document',
            'title_ar' => 'مستند العقد',
            'disk' => 'local',
            'file_path' => 'documents/document.pdf',
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 12,
            'is_public' => true,
        ], $attributes));
    }
}
