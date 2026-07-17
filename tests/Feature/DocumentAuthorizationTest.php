<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Lease;
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
            'is_public' => false,
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
            'is_public' => false,
        ]);

        $this->actingAs($tenantUser)
            ->get(route('documents.download', $document))
            ->assertForbidden();
    }
}
