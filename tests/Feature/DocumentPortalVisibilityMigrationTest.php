<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPortalVisibilityMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_only_exposes_approved_document_and_attachment_pairs(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $asset = $this->createAsset($portfolio);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner);
        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'MIGRATION-RECEIPT',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 100,
            'currency' => 'SAR',
        ]);

        $leaseContract = $this->document($portfolio->id, $owner->id, $lease, 'lease_contract', false);
        $ownerReport = $this->document($portfolio->id, $owner->id, $lease, 'owner_report', true);
        $assetContract = $this->document($portfolio->id, $owner->id, $asset, 'lease_contract', true);
        $receipt = $this->document($portfolio->id, $owner->id, $payment, 'receipt', false);

        $migration = require database_path('migrations/2026_07_21_000001_normalize_document_portal_visibility.php');
        $migration->up();

        $this->assertTrue($leaseContract->fresh()->is_public);
        $this->assertFalse($ownerReport->fresh()->is_public);
        $this->assertFalse($assetContract->fresh()->is_public);
        $this->assertTrue($receipt->fresh()->is_public);
    }

    private function document(
        int $portfolioId,
        int $uploaderId,
        Model $attachment,
        string $type,
        bool $portalVisible,
    ): Document {
        return Document::query()->create([
            'portfolio_id' => $portfolioId,
            'uploaded_by_user_id' => $uploaderId,
            'documentable_type' => $attachment->getMorphClass(),
            'documentable_id' => $attachment->id,
            'type' => $type,
            'title_en' => $type,
            'title_ar' => 'مستند',
            'disk' => 'local',
            'file_path' => "documents/{$type}-{$attachment->id}.pdf",
            'original_name' => "{$type}.pdf",
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_public' => $portalVisible,
        ]);
    }
}
