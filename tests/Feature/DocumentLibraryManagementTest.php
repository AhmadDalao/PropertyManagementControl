<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Lease;
use App\Modules\Documents\Actions\ManageDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
        $otherLease = $this->createLease(
            $portfolio,
            $tenantProfile,
            $this->createAsset($portfolio),
            $owner,
            ['status' => 'expired'],
        );

        $response = $this->actingAs($owner)
            ->post(route('documents.store'), [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'signed_contract',
                'title_en' => 'Signed contract',
                'title_ar' => 'العقد الموقع',
                'is_public' => true,
                'file' => $this->fakePdf('signed-contract.pdf'),
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
                'documentable_id' => $otherLease->id,
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
        $this->assertSame($lease->id, $document->documentable_id);

        $this->actingAs($owner)
            ->get(route('documents.download', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

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
                'title_ar' => 'عقد خارجي',
                'file' => $this->fakePdf('foreign.pdf'),
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

        $this->actingAs($owner)
            ->post(route('documents.store'), [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'signed_contract',
                'title_en' => 'Wrong extension signed contract',
                'file' => UploadedFile::fake()->create('signed-contract.txt', 64, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('documents', ['title_en' => 'Wrong extension signed contract']);

        $this->actingAs($owner)
            ->post(route('documents.store'), [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'signed_contract',
                'title_en' => 'Fake signature contract',
                'title_ar' => 'عقد بتوقيع ملف مزيف',
                'file' => UploadedFile::fake()->create('fake-signature.pdf', 64, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('documents', ['title_en' => 'Fake signature contract']);
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

    public function test_document_index_uses_a_lean_explicit_attachment_payload(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Document Owner']);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
            ['code' => 'DOC-LEASE-100'],
        );
        Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'lease_contract',
            'title_en' => 'Lean contract',
            'title_ar' => 'عقد مختصر',
            'disk' => 'local',
            'file_path' => 'documents/lean.pdf',
            'original_name' => 'lean.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_public' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('documents.index', ['search' => 'DOC-LEASE-100']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('documents.total', 1)
                ->where('documents.data.0.attachment.label', 'DOC-LEASE-100')
                ->where('documents.data.0.uploaded_by.name', 'Document Owner')
                ->missing('documents.data.0.documentable')
                ->missing('assetOptions')
                ->missing('leaseOptions')
                ->missing('paymentOptions'));
    }

    public function test_document_table_handles_page_sizes_sorting_filters_and_xlsx_export(): void
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
        $now = now();

        Document::query()->insert(collect(range(1, 31))->map(
            fn (int $number): array => [
                'portfolio_id' => $portfolio->id,
                'uploaded_by_user_id' => $owner->id,
                'documentable_type' => $lease->getMorphClass(),
                'documentable_id' => $lease->id,
                'type' => $number % 2 === 0 ? 'signed_contract' : 'owner_report',
                'title_en' => sprintf('Scale document %03d', $number),
                'title_ar' => sprintf('مستند اختبار %03d', $number),
                'disk' => 'local',
                'file_path' => sprintf('documents/scale-%03d.pdf', $number),
                'original_name' => sprintf('scale-%03d.pdf', $number),
                'mime_type' => 'application/pdf',
                'file_size' => 100 + $number,
                'is_public' => $number % 2 === 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        )->all());

        foreach ([10, 25, 50, 100] as $perPage) {
            $this->actingAs($owner)
                ->get(route('documents.index', ['per_page' => $perPage]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('documents.total', 31)
                    ->where('documents.per_page', $perPage)
                    ->has('documents.data', min($perPage, 31)));
        }

        $filters = [
            'type' => 'signed_contract',
            'visibility' => 'public',
            'sort' => 'title_en',
            'direction' => 'asc',
            'per_page' => 10,
        ];

        $this->actingAs($owner)
            ->get(route('documents.index', $filters))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('documents.total', 15)
                ->has('documents.data', 10)
                ->where('documents.data.0.title_en', 'Scale document 002')
                ->where('filters.type', 'signed_contract')
                ->where('filters.visibility', 'public'));

        $this->actingAs($owner)
            ->get(route('documents.index', [...$filters, 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('documents.current_page', 2)
                ->has('documents.data', 5));

        $export = $this->actingAs($owner)
            ->get(route('exports.resource', ['resource' => 'documents', ...$filters]))
            ->assertOk();
        $worksheet = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('Scale document 002', $worksheet);
        $this->assertStringNotContainsString('Scale document 001', $worksheet);
    }

    public function test_prefilled_upload_locks_the_attachment_and_edit_cannot_replace_the_pdf(): void
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
            ['code' => 'DOC-FORM-1'],
        );
        $document = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'signed_contract',
            'title_en' => 'Locked contract',
            'title_ar' => 'عقد ثابت',
            'disk' => 'local',
            'file_path' => 'documents/locked.pdf',
            'original_name' => 'locked.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_public' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('documents.create', [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'signed_contract',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.initialValues.documentable_type', 'lease')
                ->where('formPage.initialValues.documentable_id', (string) $lease->id)
                ->where('formPage.initialValues.is_public', true)
                ->where('formPage.description', fn (string $description): bool => str_contains($description, 'DOC-FORM-1'))
                ->where('formPage.fields', function ($fields): bool {
                    $fields = collect($fields)->keyBy('name');

                    return data_get($fields, 'documentable_type.type') === 'hidden'
                        && data_get($fields, 'documentable_id.type') === 'hidden'
                        && data_get($fields, 'file.accept') === '.pdf,application/pdf';
                }));

        $this->actingAs($owner)
            ->get(route('documents.edit', $document))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.fields', fn ($fields): bool => collect($fields)
                    ->pluck('name')
                    ->intersect(['documentable_type', 'documentable_id', 'file'])
                    ->isEmpty()));
    }

    public function test_document_workspace_and_form_are_fully_localized_in_arabic(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('app.translations.documents.title', 'المستندات')
                ->where('counts.0.label', 'الكل'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('documents.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.title', 'رفع المستند')
                ->where('formPage.description', 'اربط ملف PDF خاصاً بعقد أو أصل أو دفعة.')
                ->where('formPage.fields', fn ($fields): bool => collect($fields)
                    ->contains(fn (array $field): bool => ($field['name'] ?? null) === 'file'
                        && ($field['label'] ?? null) === 'ملف PDF')));
    }

    public function test_internal_document_type_cannot_be_marked_portal_visible(): void
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

        $this->actingAs($owner)
            ->post(route('documents.store'), [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'owner_report',
                'title_en' => 'Internal report',
                'title_ar' => 'تقرير داخلي',
                'is_public' => true,
                'file' => $this->fakePdf('internal-report.pdf'),
            ])
            ->assertRedirect();

        $this->assertFalse(Document::query()->where('title_en', 'Internal report')->firstOrFail()->is_public);
    }

    public function test_document_action_rejects_cross_portfolio_delete_when_reused_directly(): void
    {
        $ownerPortfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $ownerPortfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $tenant = $this->createTenantProfile(
            $foreignPortfolio,
            $this->createUserWithRole('tenant', $foreignPortfolio),
        );
        $lease = $this->createLease(
            $foreignPortfolio,
            $tenant,
            $this->createAsset($foreignPortfolio),
            $foreignOwner,
        );
        $document = Document::query()->create([
            'portfolio_id' => $foreignPortfolio->id,
            'uploaded_by_user_id' => $foreignOwner->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'lease_contract',
            'title_en' => 'Foreign action document',
            'title_ar' => 'مستند خارجي',
            'disk' => 'local',
            'file_path' => 'documents/foreign-action.pdf',
            'original_name' => 'foreign-action.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_public' => true,
        ]);

        try {
            app(ManageDocuments::class)->delete($owner, $document);
            $this->fail('Cross-portfolio document mutation was not rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }
}
