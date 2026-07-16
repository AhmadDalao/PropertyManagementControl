<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Lease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentLibraryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_update_and_delete_a_scoped_lease_document(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenantProfile = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease($portfolio, $tenantProfile, $this->createAsset($portfolio), $owner);

        $response = $this->actingAs($owner)
            ->post(route('documents.store'), [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'signed_contract',
                'title_en' => 'Signed contract',
                'title_ar' => 'العقد الموقع',
                'is_public' => true,
                'file' => UploadedFile::fake()->create('signed-contract.pdf', 64, 'application/pdf'),
            ]);

        $document = Document::query()->firstOrFail();

        $response->assertRedirect(route('documents.show', $document));

        $this->assertSame($portfolio->id, $document->portfolio_id);
        $this->assertSame('lease', $document->documentable_type);
        $this->assertSame($lease->id, $document->documentable_id);
        $this->assertTrue($document->is_public);
        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($owner)
            ->put(route('documents.update', $document), [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'tenant_statement',
                'title_en' => 'Tenant statement',
                'title_ar' => 'كشف المستأجر',
                'is_public' => false,
            ])
            ->assertRedirect(route('documents.show', $document));

        $document->refresh();

        $this->assertSame('Tenant statement', $document->title_en);
        $this->assertSame('tenant_statement', $document->type);
        $this->assertFalse($document->is_public);

        $path = $document->file_path;

        $this->actingAs($owner)
            ->delete(route('documents.destroy', $document))
            ->assertRedirect(route('documents.index'));

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_owner_cannot_attach_a_document_to_a_foreign_portfolio_record(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $foreignTenant = $this->createUserWithRole('tenant', $foreignPortfolio);
        $foreignLease = $this->createLease(
            $foreignPortfolio,
            $this->createTenantProfile($foreignPortfolio, $foreignTenant),
            $this->createAsset($foreignPortfolio),
            $foreignOwner,
        );

        $this->actingAs($owner)
            ->post(route('documents.store'), [
                'documentable_type' => 'lease',
                'documentable_id' => $foreignLease->id,
                'type' => 'signed_contract',
                'title_en' => 'Foreign contract',
                'file' => UploadedFile::fake()->create('foreign.pdf', 32, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('documents', ['title_en' => 'Foreign contract']);
    }

    public function test_document_uploads_must_be_pdf_files(): void
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
            ->post(route('documents.store'), [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'signed_contract',
                'title_en' => 'Bad signed contract',
                'file' => UploadedFile::fake()->image('signed-contract.jpg'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('documents', ['title_en' => 'Bad signed contract']);

        $this->actingAs($owner)
            ->post(route('documents.store'), [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'signed_contract',
                'title_en' => 'Spoofed signed contract',
                'file' => UploadedFile::fake()->create('signed-contract.pdf', 64, 'text/plain'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('documents', ['title_en' => 'Spoofed signed contract']);
    }

    public function test_document_index_and_export_do_not_leak_foreign_portfolio_documents(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);

        $ownLease = $this->createLease(
            $portfolio,
            $this->createTenantProfile($portfolio, $this->createUserWithRole('tenant', $portfolio)),
            $this->createAsset($portfolio),
            $owner,
        );
        $foreignLease = $this->createLease(
            $foreignPortfolio,
            $this->createTenantProfile($foreignPortfolio, $this->createUserWithRole('tenant', $foreignPortfolio)),
            $this->createAsset($foreignPortfolio),
            $foreignOwner,
        );

        Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => Lease::class,
            'documentable_id' => $ownLease->id,
            'type' => 'lease_contract',
            'title_en' => 'Own lease contract',
            'disk' => 'local',
            'file_path' => 'documents/own.pdf',
            'original_name' => 'own.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
        ]);
        Document::query()->create([
            'portfolio_id' => $foreignPortfolio->id,
            'uploaded_by_user_id' => $foreignOwner->id,
            'documentable_type' => Lease::class,
            'documentable_id' => $foreignLease->id,
            'type' => 'lease_contract',
            'title_en' => 'Foreign lease contract',
            'disk' => 'local',
            'file_path' => 'documents/foreign.pdf',
            'original_name' => 'foreign.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
        ]);

        $this->actingAs($owner)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('Own lease contract')
            ->assertDontSee('Foreign lease contract');

        $export = $this->actingAs($owner)
            ->get(route('exports.resource', 'documents'))
            ->assertOk();

        $sheetXml = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('Own lease contract', $sheetXml);
        $this->assertStringNotContainsString('Foreign lease contract', $sheetXml);
    }

    public function test_tenant_cannot_open_the_admin_document_library(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        $this->actingAs($tenant)
            ->get(route('documents.index'))
            ->assertForbidden();
    }
}
