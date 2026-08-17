<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Modules\Documents\Actions\ManageDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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
                'issued_on' => '2026-01-01',
                'expires_on' => '2026-12-31',
                'is_public' => true,
                'file' => $this->fakePdf('signed-contract.pdf'),
            ]);

        $document = Document::query()->firstOrFail();

        $response->assertRedirect(route('documents.show', $document));

        $this->assertSame($portfolio->id, $document->portfolio_id);
        $this->assertSame('lease', $document->documentable_type);
        $this->assertSame($lease->id, $document->documentable_id);
        $this->assertTrue($document->is_public);
        $this->assertSame('2026-01-01', $document->issued_on?->toDateString());
        $this->assertSame('2026-12-31', $document->expires_on?->toDateString());
        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($owner)
            ->put(route('documents.update', $document), [
                'documentable_type' => 'lease',
                'documentable_id' => $otherLease->id,
                'type' => 'tenant_statement',
                'title_en' => 'Tenant statement',
                'title_ar' => 'كشف المستأجر',
                'issued_on' => '2026-02-01',
                'expires_on' => '2027-01-31',
                'is_public' => false,
            ])
            ->assertRedirect(route('documents.show', $document));

        $document->refresh();

        $this->assertSame('Tenant statement', $document->title_en);
        $this->assertSame('tenant_statement', $document->type);
        $this->assertFalse($document->is_public);
        $this->assertSame($lease->id, $document->documentable_id);
        $this->assertSame('2027-01-31', $document->expires_on?->toDateString());

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

    public function test_document_validity_filters_metrics_details_and_export_share_one_scope(): void
    {
        $this->travelTo('2026-08-02 10:00:00');
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Compliance Tower']);
        $foreignAsset = $this->createAsset($foreignPortfolio);
        $documents = [
            ['Expired insurance', today()->subDay()],
            ['Permit due in 30 days', today()->addDays(15)],
            ['Certificate due in 90 days', today()->addDays(60)],
            ['Current license', today()->addDays(120)],
            ['Permanent title deed', null],
        ];

        foreach ($documents as [$title, $expiresOn]) {
            Document::query()->create([
                'portfolio_id' => $portfolio->id,
                'uploaded_by_user_id' => $owner->id,
                'documentable_type' => $asset->getMorphClass(),
                'documentable_id' => $asset->id,
                'type' => 'other',
                'title_en' => $title,
                'title_ar' => 'مستند صلاحية',
                'issued_on' => today()->subYear(),
                'expires_on' => $expiresOn,
                'disk' => 'local',
                'file_path' => 'documents/'.str($title)->slug().'.pdf',
                'original_name' => str($title)->slug().'.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 100,
            ]);
        }

        Document::query()->create([
            'portfolio_id' => $foreignPortfolio->id,
            'uploaded_by_user_id' => $foreignOwner->id,
            'documentable_type' => $foreignAsset->getMorphClass(),
            'documentable_id' => $foreignAsset->id,
            'type' => 'other',
            'title_en' => 'Foreign expired permit',
            'title_ar' => 'تصريح خارجي منتهي',
            'issued_on' => today()->subYear(),
            'expires_on' => today()->subDay(),
            'disk' => 'local',
            'file_path' => 'documents/foreign-expired.pdf',
            'original_name' => 'foreign-expired.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
        ]);

        $this->actingAs($owner)
            ->get(route('documents.index', [
                'expiry' => 'attention',
                'property_id' => $asset->id,
                'sort' => 'expires_on',
                'direction' => 'asc',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.expiry', 'attention')
                ->where('documents.total', 3)
                ->where('documents.data.0.title_en', 'Expired insurance')
                ->where('documents.data.0.expiry_status', 'expired')
                ->where('documents.data.0.expiry_days', -1)
                ->where('documentInsights.total', 5)
                ->where('documentInsights.expired', 1)
                ->where('documentInsights.expiring_90', 2)
                ->where('documentInsights.no_expiry', 1)
                ->where('counts.1.value', 3)
                ->where('counts.2.value', 1)
                ->where('counts.3.value', 1)
                ->where('counts.4.value', 1));

        $export = $this->actingAs($owner)
            ->get(route('exports.resource', [
                'resource' => 'documents',
                'expiry' => 'attention',
                'property_id' => $asset->id,
            ]))
            ->assertOk();
        $worksheet = $this->xlsxWorksheetXml($export);

        $this->assertStringContainsString('Validity status', $worksheet);
        $this->assertStringContainsString('Expired insurance', $worksheet);
        $this->assertStringContainsString('Permit due in 30 days', $worksheet);
        $this->assertStringContainsString('Certificate due in 90 days', $worksheet);
        $this->assertStringNotContainsString('Current license', $worksheet);
        $this->assertStringNotContainsString('Permanent title deed', $worksheet);
        $this->assertStringNotContainsString('Foreign expired permit', $worksheet);

        $expired = Document::query()->where('title_en', 'Expired insurance')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('documents.show', $expired))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/documents/show')
                ->where('detailPage.availableTabs', ['overview', 'access', 'validity', 'history'])
                ->where('detailPage.stats.1.value', 'Expired')
                ->where('detailPage.sections.0.key', 'identity')
                ->where('detailPage.sections.1.key', 'ownership')
                ->where('detailPage.sections.2.key', 'access')
                ->where('detailPage.sections.3.key', 'validity')
                ->where('detailPage.sections.3.items', fn ($items): bool => collect($items)
                    ->contains(fn (array $item): bool => $item['label'] === 'Issued on'
                        && $item['value'] === '2025-08-02'))
                ->where('detailPage.workflow.title', 'Replace this expired document')
                ->where('detailPage.replacement.can_upload', true)
                ->where('detailPage.replacement.upload_url', fn (string $url): bool => str_contains($url, 'documentable_type=asset')
                    && str_contains($url, 'documentable_id='.$asset->id))
                ->missing('detailPage.decisionCards')
                ->missing('detailPage.documents'));

        $this->actingAs($owner)
            ->get(route('reports.index', ['property_id' => $asset->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('reportLibrary.2.cards', fn ($cards): bool => collect($cards)
                    ->contains(fn (array $card): bool => $card['key'] === 'document-expiry'
                        && str_contains($card['openHref'], 'expiry=attention')
                        && str_contains($card['downloads'][0]['href'], 'expiry=attention'))));
    }

    public function test_property_filter_scopes_asset_lease_and_payment_documents_to_one_hierarchy(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $building = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'rentable' => false,
            'title_en' => 'Scoped Tower',
        ]);
        $unit = $this->createAsset($portfolio, [
            'parent_id' => $building->id,
            'title_en' => 'Scoped Unit',
        ]);
        $otherBuilding = $this->createAsset($portfolio, [
            'asset_type' => 'building',
            'rentable' => false,
            'title_en' => 'Other Tower',
        ]);
        $otherUnit = $this->createAsset($portfolio, [
            'parent_id' => $otherBuilding->id,
            'title_en' => 'Other Unit',
        ]);
        $foreignBuilding = $this->createAsset($foreignPortfolio, [
            'asset_type' => 'building',
            'rentable' => false,
        ]);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease($portfolio, $tenant, $unit, $owner);
        $otherLease = $this->createLease(
            $portfolio,
            $tenant,
            $otherUnit,
            $owner,
            ['status' => 'expired'],
        );
        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'SCOPED-DOC-PAYMENT',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 500,
            'currency' => 'SAR',
        ]);

        foreach ([
            [$building, 'Scoped building PDF', 'owner_report'],
            [$unit, 'Scoped unit PDF', 'owner_report'],
            [$lease, 'Scoped lease PDF', 'signed_contract'],
            [$payment, 'Scoped payment PDF', 'receipt'],
            [$otherBuilding, 'Other building PDF', 'owner_report'],
            [$otherLease, 'Other lease PDF', 'signed_contract'],
        ] as [$record, $title, $type]) {
            Document::query()->create([
                'portfolio_id' => $portfolio->id,
                'uploaded_by_user_id' => $owner->id,
                'documentable_type' => $record->getMorphClass(),
                'documentable_id' => $record->id,
                'type' => $type,
                'title_en' => $title,
                'title_ar' => 'مستند تجريبي',
                'disk' => 'local',
                'file_path' => 'documents/'.str($title)->slug().'.pdf',
                'original_name' => str($title)->slug().'.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 100,
                'is_public' => true,
            ]);
        }

        $filters = ['property_id' => $building->id];

        $this->actingAs($owner)
            ->get(route('documents.index', $filters))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.property_id', (string) $building->id)
                ->where('documents.total', 4)
                ->where('documentInsights.total', 4)
                ->where('counts.0.value', 4)
                ->where(
                    'documents.data',
                    fn ($documents): bool => collect($documents)
                        ->pluck('title_en')
                        ->sort()
                        ->values()
                        ->all() === [
                            'Scoped building PDF',
                            'Scoped lease PDF',
                            'Scoped payment PDF',
                            'Scoped unit PDF',
                        ],
                )
                ->where(
                    'propertyOptions',
                    fn ($options): bool => collect($options)->contains(
                        fn (array $option): bool => $option['id'] === $building->id,
                    ),
                ));

        $worksheet = $this->xlsxWorksheetXml(
            $this->actingAs($owner)->get(route('exports.resource', [
                'resource' => 'documents',
                ...$filters,
            ])),
        );

        $this->assertStringContainsString('Scoped lease PDF', $worksheet);
        $this->assertStringContainsString('Scoped payment PDF', $worksheet);
        $this->assertStringNotContainsString('Other building PDF', $worksheet);
        $this->assertStringNotContainsString('Other lease PDF', $worksheet);

        $this->actingAs($owner)
            ->get(route('documents.index', ['property_id' => $foreignBuilding->id]))
            ->assertForbidden();
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
                ->where('formPage.initialValues.issued_on', $lease->started_at?->toDateString())
                ->where('formPage.initialValues.expires_on', $lease->ends_at?->toDateString())
                ->where('formPage.description', fn (string $description): bool => str_contains($description, 'DOC-FORM-1'))
                ->where('formPage.fields', function ($fields): bool {
                    $fields = collect($fields)->keyBy('name');

                    return data_get($fields, 'documentable_type.type') === 'hidden'
                        && data_get($fields, 'documentable_id.type') === 'hidden'
                        && data_get($fields, 'issued_on.type') === 'date'
                        && data_get($fields, 'expires_on.type') === 'date'
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

        $this->actingAs($owner)
            ->put(route('documents.update', $document), [
                'type' => 'signed_contract',
                'title_en' => 'Invalid validity',
                'title_ar' => 'صلاحية غير صحيحة',
                'issued_on' => '2026-12-31',
                'expires_on' => '2026-01-01',
            ])
            ->assertSessionHasErrors('expires_on');

        $this->assertSame('Locked contract', $document->fresh()->title_en);
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
                ->where('formPage.description', 'اربط ملف PDF خاصاً بعقد أو عقار أو دفعة أو مصروف.')
                ->where('formPage.fields', function ($fields): bool {
                    $fields = collect($fields)->keyBy('name');

                    return data_get($fields, 'file.label') === 'ملف PDF'
                        && data_get($fields, 'issued_on.label') === 'تاريخ الإصدار'
                        && data_get($fields, 'expires_on.label') === 'تاريخ الانتهاء'
                        && collect(data_get($fields, 'type.options', []))
                            ->contains(fn (array $option): bool => ($option['value'] ?? null) === 'signed_contract'
                                && ($option['label'] ?? null) === 'العقد الموقع');
                }));
    }

    public function test_document_detail_prioritizes_the_pdf_download_in_english_and_arabic(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $asset = $this->createAsset($portfolio);
        $document = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $asset->getMorphClass(),
            'documentable_id' => $asset->id,
            'type' => 'owner_report',
            'title_en' => 'Owner operating report',
            'title_ar' => 'تقرير تشغيل المالك',
            'disk' => 'local',
            'file_path' => 'documents/owner-operating-report.pdf',
            'original_name' => 'owner-operating-report.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 128,
        ]);

        $this->actingAs($owner)
            ->get(route('documents.show', $document))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/documents/show')
                ->where('detailPage.availableTabs', ['overview', 'access', 'validity', 'history'])
                ->where('detailPage.header.actions.0.label', 'Download PDF')
                ->where('detailPage.header.actions.0.href', route('documents.download', $document))
                ->where('detailPage.header.actions.0.variant', 'primary')
                ->where('detailPage.header.actions.0.external', true)
                ->where('detailPage.header.actions.1.label', 'Edit document')
                ->where('detailPage.header.actions.1.href', route('documents.edit', $document))
                ->where('detailPage.header.actions.1.variant', 'secondary')
                ->where('detailPage.header.actions.2.label', 'Delete document')
                ->where('detailPage.header.actions.2.method', 'delete')
                ->where('detailPage.sections.2.items', fn ($items): bool => collect($items)
                    ->contains(fn (array $item): bool => $item['label'] === 'Portal eligibility'
                        && $item['value'] === 'This type is always management-only'))
                ->where('detailPage.replacement.can_upload', true)
                ->where('detailPage.replacement.upload_url', fn (string $url): bool => str_contains($url, 'is_public=0')));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('documents.show', $document))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.header.actions.0.label', 'تنزيل PDF')
                ->where('detailPage.header.actions.0.variant', 'primary')
                ->where('detailPage.header.actions.1.label', 'تعديل المستند')
                ->where('detailPage.header.actions.1.variant', 'secondary')
                ->where('detailPage.header.actions.2.label', 'حذف المستند')
                ->where('detailPage.workflow.title', 'سجل مستند دائم')
                ->where('app.translations.documents.tabs.access', 'الوصول')
                ->where('app.translations.documents.tabs.validity', 'الصلاحية'));
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

    public function test_document_detail_explains_portal_access_and_payment_proof_replacement_rules(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'DOC-PROOF-1',
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'pending',
            'received_on' => today(),
            'amount' => 500,
            'currency' => 'SAR',
        ]);
        $contract = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'signed_contract',
            'title_en' => 'Portal contract',
            'title_ar' => 'عقد البوابة',
            'disk' => 'local',
            'file_path' => 'documents/portal-contract.pdf',
            'original_name' => 'portal-contract.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_public' => true,
        ]);
        $proof = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $tenantUser->id,
            'documentable_type' => $payment->getMorphClass(),
            'documentable_id' => $payment->id,
            'type' => 'payment_proof',
            'title_en' => 'Tenant bank proof',
            'title_ar' => 'إثبات البنك للمستأجر',
            'disk' => 'local',
            'file_path' => 'documents/tenant-bank-proof.pdf',
            'original_name' => 'tenant-bank-proof.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_public' => false,
            'meta_json' => ['review_status' => 'pending'],
        ]);

        $this->actingAs($owner)
            ->get(route('documents.show', $contract))
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.sections.2.items', fn ($items): bool => collect($items)
                    ->contains(fn (array $item): bool => $item['label'] === 'Authorized audience'
                        && $item['value'] === 'Owning tenant and authorized management'))
                ->where('detailPage.replacement.can_upload', true)
                ->where('detailPage.replacement.upload_url', fn (string $url): bool => str_contains($url, 'is_public=1')));

        $this->actingAs($owner)
            ->get(route('documents.create', [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'signed_contract',
                'is_public' => 0,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.initialValues.is_public', false));

        $this->actingAs($owner)
            ->get(route('documents.show', $proof))
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.replacement.can_upload', false)
                ->where('detailPage.replacement.upload_url', null)
                ->where('detailPage.sections.2.items', fn ($items): bool => collect($items)
                    ->contains(fn (array $item): bool => $item['label'] === 'Payment-proof review'
                        && $item['value'] === 'Pending')));
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

    public function test_document_action_rejects_non_pdf_uploads_when_reused_directly(): void
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

        try {
            app(ManageDocuments::class)->create($owner, [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'signed_contract',
                'title_en' => 'Direct fake upload',
                'title_ar' => 'رفع مباشر مزيف',
                'file' => UploadedFile::fake()->image('not-a-contract.jpg'),
            ]);
            $this->fail('A direct document action accepted a non-PDF upload.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }

        $this->assertDatabaseMissing('documents', ['title_en' => 'Direct fake upload']);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_document_action_derives_authoritative_ownership_and_portal_visibility(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignOwner = $this->createUserWithRole('owner', $foreignPortfolio);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile(
                $portfolio,
                $this->createUserWithRole('tenant', $portfolio),
            ),
            $this->createAsset($portfolio),
            $owner,
        );

        $document = app(ManageDocuments::class)->create($owner, [
            'portfolio_id' => $foreignPortfolio->id,
            'uploaded_by_user_id' => $foreignOwner->id,
            'documentable_type' => 'lease',
            'documentable_id' => $lease->id,
            'type' => 'owner_report',
            'title_en' => '  Authoritative document  ',
            'title_ar' => '  مستند موثوق  ',
            'is_public' => true,
            'disk' => 'public',
            'mime_type' => 'text/html',
            'file' => $this->fakePdf('authoritative.pdf'),
        ]);

        $this->assertSame($portfolio->id, $document->portfolio_id);
        $this->assertSame($owner->id, $document->uploaded_by_user_id);
        $this->assertSame('Authoritative document', $document->title_en);
        $this->assertSame('مستند موثوق', $document->title_ar);
        $this->assertSame('local', $document->disk);
        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertFalse($document->is_public);
        $this->assertStringStartsWith("documents/library/{$portfolio->id}/", $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_document_action_rejects_invalid_direct_metadata_updates(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile(
                $portfolio,
                $this->createUserWithRole('tenant', $portfolio),
            ),
            $this->createAsset($portfolio),
            $owner,
        );
        $document = app(ManageDocuments::class)->create($owner, [
            'documentable_type' => 'lease',
            'documentable_id' => $lease->id,
            'type' => 'signed_contract',
            'title_en' => 'Original metadata',
            'title_ar' => 'البيانات الأصلية',
            'file' => $this->fakePdf('original-metadata.pdf'),
        ]);

        try {
            app(ManageDocuments::class)->update($owner, $document, [
                'type' => 'executable_file',
                'title_en' => ['not', 'text'],
                'title_ar' => '',
                'is_public' => true,
            ]);
            $this->fail('A direct document update accepted malformed metadata.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('type', $exception->errors());
            $this->assertArrayHasKey('title_en', $exception->errors());
            $this->assertArrayHasKey('title_ar', $exception->errors());
        }

        $this->assertSame('Original metadata', $document->fresh()->title_en);
        $this->assertSame('signed_contract', $document->fresh()->type);
    }

    public function test_document_action_rejects_uploads_for_inactive_portfolios(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio(['status' => 'inactive']);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile(
                $portfolio,
                $this->createUserWithRole('tenant', $portfolio),
            ),
            $this->createAsset($portfolio),
            $owner,
        );

        try {
            app(ManageDocuments::class)->create($owner, [
                'documentable_type' => 'lease',
                'documentable_id' => $lease->id,
                'type' => 'signed_contract',
                'title_en' => 'Inactive portfolio document',
                'title_ar' => 'مستند محفظة غير نشطة',
                'file' => $this->fakePdf('inactive.pdf'),
            ]);
            $this->fail('An inactive portfolio accepted a document upload.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('documentable_id', $exception->errors());
        }

        $this->assertDatabaseMissing('documents', ['title_en' => 'Inactive portfolio document']);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_document_directory_normalizes_filters_and_omits_storage_paths(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile(
                $portfolio,
                $this->createUserWithRole('tenant', $portfolio),
            ),
            $this->createAsset($portfolio),
            $owner,
        );
        app(ManageDocuments::class)->create($owner, [
            'documentable_type' => 'lease',
            'documentable_id' => $lease->id,
            'type' => 'signed_contract',
            'title_en' => 'Lean secure document',
            'title_ar' => 'مستند آمن مختصر',
            'file' => $this->fakePdf('lean-secure.pdf'),
        ]);

        $this->actingAs($owner)
            ->get(route('documents.index', [
                'search' => 'Lean secure document',
                'type' => 'invalid-type',
                'attachment' => 'invalid-attachment',
                'visibility' => 'invalid-visibility',
                'date_from' => '2026-99-99',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('documents.total', 1)
                ->where('filters.type', 'all')
                ->where('filters.attachment', 'all')
                ->where('filters.visibility', 'all')
                ->where('filters.date_from', '')
                ->missing('documents.data.0.disk')
                ->missing('documents.data.0.file_path')
                ->missing('documents.data.0.mime_type'));
    }

    public function test_arabic_document_export_uses_localized_headers_and_types(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $lease = $this->createLease(
            $portfolio,
            $this->createTenantProfile(
                $portfolio,
                $this->createUserWithRole('tenant', $portfolio),
            ),
            $this->createAsset($portfolio),
            $owner,
        );
        app(ManageDocuments::class)->create($owner, [
            'documentable_type' => 'lease',
            'documentable_id' => $lease->id,
            'type' => 'signed_contract',
            'title_en' => 'Arabic export document',
            'title_ar' => 'مستند التصدير العربي',
            'file' => $this->fakePdf('arabic-export.pdf'),
        ]);

        $export = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('exports.resource', ['resource' => 'documents']));

        $export->assertOk();
        $sheet = $this->xlsxWorksheetXml($export);
        $this->assertStringContainsString('العنوان بالإنجليزية', $sheet);
        $this->assertStringContainsString('العقد الموقع', $sheet);
        $this->assertStringNotContainsString('Original File', $sheet);
    }
}
