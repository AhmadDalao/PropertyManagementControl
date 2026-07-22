<?php

namespace Tests\Feature;

use App\Models\CmsSection;
use App\Models\MediaFile;
use App\Modules\Media\Actions\ManageMediaFiles;
use App\Modules\Media\Support\MediaFileStorage;
use App\Modules\Media\Support\MediaOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MediaModuleSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_media_index_is_paginated_transformed_and_portfolio_scoped(): void
    {
        $portfolio = $this->createPortfolio(['name_en' => 'Visible portfolio']);
        $otherPortfolio = $this->createPortfolio(['name_en' => 'Hidden portfolio']);
        $owner = $this->createUserWithRole('owner', $portfolio);

        foreach (range(1, 12) as $index) {
            $this->media([
                'portfolio_id' => $portfolio->id,
                'uploaded_by_user_id' => $owner->id,
                'title_en' => sprintf('Visible image %02d', $index),
                'path' => sprintf('media/visible-%02d.jpg', $index),
            ]);
        }

        $this->media([
            'portfolio_id' => $otherPortfolio->id,
            'title_en' => 'Hidden image',
            'path' => 'media/hidden.jpg',
        ]);
        $this->media([
            'portfolio_id' => null,
            'title_en' => 'Global image',
            'path' => 'media/global.jpg',
        ]);

        $this->actingAs($owner)
            ->get(route('media-files.index', ['per_page' => 10, 'sort' => 'title_en']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/media/index')
                ->where('mediaFiles.total', 12)
                ->has('mediaFiles.data', 10)
                ->where('mediaInsights.total', 12)
                ->has('mediaFiles.data.0.file_url')
                ->missing('mediaFiles.data.0.path'));

        $this->actingAs($owner)
            ->get(route('media-files.index', ['search' => 'Hidden image']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('mediaFiles.total', 0));
    }

    public function test_private_upload_uses_private_storage_and_authorized_file_response(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $otherOwner = $this->createUserWithRole('owner', $this->createPortfolio());

        $this->actingAs($owner)
            ->post(route('media-files.store'), $this->uploadPayload([
                'portfolio_id' => $portfolio->id,
                'visibility' => 'private',
                'file' => UploadedFile::fake()->image('private-image.jpg', 160, 90),
            ]))
            ->assertRedirect();

        $mediaFile = MediaFile::query()->firstOrFail();

        $this->assertSame('local', $mediaFile->disk);
        $this->assertSame(160, $mediaFile->width);
        $this->assertSame(90, $mediaFile->height);
        Storage::disk('local')->assertExists($mediaFile->path);
        Storage::disk('public')->assertMissing($mediaFile->path);

        $this->actingAs($owner)
            ->get(route('media-files.file', $mediaFile))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->actingAs($otherOwner)
            ->get(route('media-files.file', $mediaFile))
            ->assertForbidden();
    }

    public function test_visibility_changes_move_the_image_between_public_and_private_storage(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->post(route('media-files.store'), $this->uploadPayload([
                'portfolio_id' => $portfolio->id,
            ]))
            ->assertRedirect();

        $mediaFile = MediaFile::query()->firstOrFail();
        Storage::disk('public')->assertExists($mediaFile->path);

        $this->actingAs($owner)
            ->put(route('media-files.update', $mediaFile), $this->updatePayload([
                'portfolio_id' => $portfolio->id,
                'visibility' => 'private',
            ]))
            ->assertRedirect(route('media-files.show', $mediaFile));

        $this->assertSame('local', $mediaFile->fresh()->disk);
        Storage::disk('local')->assertExists($mediaFile->path);
        Storage::disk('public')->assertMissing($mediaFile->path);

        $this->actingAs($owner)
            ->put(route('media-files.update', $mediaFile), $this->updatePayload([
                'portfolio_id' => $portfolio->id,
                'visibility' => 'public',
            ]))
            ->assertRedirect(route('media-files.show', $mediaFile));

        $this->assertSame('public', $mediaFile->fresh()->disk);
        Storage::disk('public')->assertExists($mediaFile->path);
        Storage::disk('local')->assertMissing($mediaFile->path);
    }

    public function test_non_image_public_upload_is_rejected(): void
    {
        Storage::fake('public');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->post(route('media-files.store'), $this->uploadPayload([
                'portfolio_id' => $portfolio->id,
                'file' => UploadedFile::fake()->create('payload.svg', 2, 'image/svg+xml'),
            ]))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('media_files', 0);
    }

    public function test_media_used_by_cms_cannot_be_hidden_or_deleted(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $superadmin = $this->createUserWithRole('superadmin');
        $mediaFile = $this->media([
            'portfolio_id' => null,
            'path' => 'media/website/hero.jpg',
            'title_en' => 'Website hero',
        ]);
        Storage::disk('public')->put($mediaFile->path, 'image-bytes');
        CmsSection::query()->create([
            'section_type' => 'hero',
            'name_en' => 'Hero',
            'name_ar' => 'الواجهة',
            'status' => 'active',
            'content_en' => ['image' => '/storage/'.$mediaFile->path],
            'content_ar' => ['image' => '/storage/'.$mediaFile->path],
            'settings_json' => [],
        ]);

        $this->actingAs($superadmin)
            ->put(route('media-files.update', $mediaFile), $this->updatePayload([
                'portfolio_id' => null,
                'visibility' => 'private',
            ]))
            ->assertSessionHasErrors('media');

        $this->actingAs($superadmin)
            ->delete(route('media-files.destroy', $mediaFile))
            ->assertSessionHasErrors('media');

        $this->assertDatabaseHas('media_files', ['id' => $mediaFile->id, 'visibility' => 'public']);
        Storage::disk('public')->assertExists($mediaFile->path);
    }

    public function test_cms_picker_only_exposes_public_global_images(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $portfolio = $this->createPortfolio();
        $publicGlobal = $this->media(['portfolio_id' => null, 'title_en' => 'Picker image']);
        $this->media(['portfolio_id' => null, 'title_en' => 'Private global', 'visibility' => 'private', 'disk' => 'local']);
        $this->media(['portfolio_id' => $portfolio->id, 'title_en' => 'Portfolio image']);

        $this->actingAs($superadmin)
            ->get(route('cms.sections.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/cms/section-form')
                ->has('mediaOptions', 1)
                ->where('mediaOptions.0.id', $publicGlobal->id)
                ->where('mediaOptions.0.url', '/storage/'.$publicGlobal->path));
    }

    public function test_delete_removes_the_database_record_and_private_file(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $mediaFile = $this->media([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'visibility' => 'private',
            'disk' => 'local',
            'path' => 'media/private/delete.jpg',
        ]);
        Storage::disk('local')->put($mediaFile->path, 'image-bytes');

        $this->actingAs($owner)
            ->delete(route('media-files.destroy', $mediaFile))
            ->assertRedirect(route('media-files.index'));

        $this->assertDatabaseMissing('media_files', ['id' => $mediaFile->id]);
        Storage::disk('local')->assertMissing($mediaFile->path);
    }

    public function test_direct_create_validates_input_and_derives_protected_file_metadata(): void
    {
        Storage::fake('public');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $actions = app(ManageMediaFiles::class);
        $mediaFile = $actions->create($owner, [
            ...$this->uploadPayload([
                'portfolio_id' => $portfolio->id,
                'file' => UploadedFile::fake()->image('authoritative.png', 320, 180),
            ]),
            'uploaded_by_user_id' => 999999,
            'disk' => 'local',
            'path' => '../../forged.php',
            'mime_type' => 'text/html',
            'size' => 1,
            'width' => 1,
            'height' => 1,
        ]);

        $this->assertSame($owner->id, $mediaFile->uploaded_by_user_id);
        $this->assertSame('public', $mediaFile->disk);
        $this->assertStringStartsWith("media/portfolios/{$portfolio->id}/", $mediaFile->path);
        $this->assertSame('image/png', $mediaFile->mime_type);
        $this->assertSame(320, $mediaFile->width);
        $this->assertSame(180, $mediaFile->height);
        $this->assertGreaterThan(1, $mediaFile->size);
        Storage::disk('public')->assertExists($mediaFile->path);

        try {
            $actions->create($owner, $this->uploadPayload([
                'portfolio_id' => $portfolio->id,
                'visibility' => 'executable',
            ]));
            $this->fail('Invalid visibility was accepted by a direct action call.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('visibility', $exception->errors());
        }

        try {
            $actions->create($owner, $this->uploadPayload([
                'portfolio_id' => $portfolio->id,
                'file' => UploadedFile::fake()
                    ->image('oversized.jpg', 120, 80)
                    ->size(MediaOptions::MAX_FILE_KILOBYTES + 1),
            ]));
            $this->fail('Oversized image was accepted by a direct action call.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }
    }

    public function test_create_rejects_inactive_and_cross_portfolio_targets_when_action_is_reused(): void
    {
        Storage::fake('public');

        $portfolio = $this->createPortfolio(['status' => 'archived']);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $foreignPortfolio = $this->createPortfolio();
        $foreignMedia = $this->media(['portfolio_id' => $foreignPortfolio->id]);
        $actions = app(ManageMediaFiles::class);

        try {
            $actions->create($owner, $this->uploadPayload(['portfolio_id' => $portfolio->id]));
            $this->fail('Inactive portfolio accepted a new image.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('portfolio_id', $exception->errors());
        }

        $this->expectException(HttpException::class);
        $actions->update($owner, $foreignMedia, $this->updatePayload([
            'portfolio_id' => $foreignPortfolio->id,
        ]));
    }

    public function test_superadmin_scope_move_uses_a_new_canonical_path_and_keeps_source_until_commit(): void
    {
        Storage::fake('public');

        $superadmin = $this->createUserWithRole('superadmin');
        $sourcePortfolio = $this->createPortfolio();
        $targetPortfolio = $this->createPortfolio();
        $actions = app(ManageMediaFiles::class);
        $mediaFile = $actions->create($superadmin, $this->uploadPayload([
            'portfolio_id' => $sourcePortfolio->id,
        ]));
        $sourcePath = $mediaFile->path;
        $storage = app(MediaFileStorage::class);
        $prepared = $storage->prepareRelocation($mediaFile, $targetPortfolio->id, 'public');

        $this->assertNotNull($prepared);
        Storage::disk('public')->assertExists($sourcePath);
        Storage::disk('public')->assertExists($prepared->targetPath);
        $storage->discardTarget($prepared);
        Storage::disk('public')->assertExists($sourcePath);
        Storage::disk('public')->assertMissing($prepared->targetPath);

        $updated = $actions->update($superadmin, $mediaFile, $this->updatePayload([
            'portfolio_id' => $targetPortfolio->id,
        ]));

        $this->assertSame($targetPortfolio->id, $updated->portfolio_id);
        $this->assertStringStartsWith("media/portfolios/{$targetPortfolio->id}/", $updated->path);
        Storage::disk('public')->assertExists($updated->path);
        Storage::disk('public')->assertMissing($sourcePath);
    }

    public function test_media_filters_are_normalized_and_arabic_export_is_a_real_localized_workbook(): void
    {
        $portfolio = $this->createPortfolio(['name_ar' => 'محفظة الوسائط']);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $this->media([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'title_en' => 'Workbook image',
            'title_ar' => 'صورة المصنف',
            'collection' => 'properties',
        ]);

        $this->actingAs($owner)
            ->get('/media-files?visibility=unknown&collection%5B%5D=bad&search%5B%5D=bad&sort%5B%5D=bad&per_page=999')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.visibility', 'all')
                ->where('filters.collection', 'all')
                ->where('filters.search', '')
                ->where('filters.sort', 'created_at')
                ->where('filters.per_page', 10)
                ->missing('mediaFiles.data.0.path'));

        $export = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('exports.resource', ['resource' => 'media-files']));
        $export->assertOk();
        $sheet = $this->xlsxWorksheetXml($export);
        $this->assertStringContainsString('العنوان', $sheet);
        $this->assertStringContainsString('المجموعة', $sheet);
        $this->assertStringContainsString('صورة المصنف', $sheet);
        $this->assertStringContainsString('محفظة الوسائط', $sheet);
        $this->assertStringNotContainsString('../../', $sheet);
    }

    public function test_arabic_forms_and_details_are_translated_and_portfolio_options_are_operational(): void
    {
        $activePortfolio = $this->createPortfolio(['name_ar' => 'محفظة نشطة']);
        $archivedPortfolio = $this->createPortfolio([
            'name_ar' => 'محفظة مؤرشفة',
            'status' => 'archived',
        ]);
        $superadmin = $this->createUserWithRole('superadmin');
        $mediaFile = $this->media([
            'portfolio_id' => $archivedPortfolio->id,
            'title_ar' => 'صورة المحفظة المؤرشفة',
        ]);

        $this->actingAs($superadmin)
            ->withSession(['locale' => 'ar'])
            ->get(route('media-files.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.title', 'رفع صورة')
                ->where('formPage.fields', function ($fields) use ($activePortfolio, $archivedPortfolio): bool {
                    $portfolio = collect($fields)->firstWhere('name', 'portfolio_id');
                    $values = collect($portfolio['options'] ?? [])->pluck('value')->map(fn ($value): string => (string) $value);

                    return $values->contains((string) $activePortfolio->id)
                        && ! $values->contains((string) $archivedPortfolio->id);
                }));

        $this->actingAs($superadmin)
            ->withSession(['locale' => 'ar'])
            ->get(route('media-files.edit', $mediaFile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.title', 'تعديل الوسائط')
                ->where('formPage.fields', function ($fields) use ($archivedPortfolio): bool {
                    $portfolio = collect($fields)->firstWhere('name', 'portfolio_id');

                    return collect($portfolio['options'] ?? [])->contains(
                        fn (array $option): bool => (string) $option['value'] === (string) $archivedPortfolio->id,
                    );
                }));

        $this->actingAs($superadmin)
            ->withSession(['locale' => 'ar'])
            ->get(route('media-files.show', $mediaFile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.header.title', 'صورة المحفظة المؤرشفة')
                ->where('detailPage.sections.0.title', 'سجل الوسائط')
                ->where('detailPage.sections.0.items.4.value', 'محفظة مؤرشفة'));
    }

    /** @param array<string, mixed> $overrides */
    private function media(array $overrides = []): MediaFile
    {
        return MediaFile::query()->create([
            'uploaded_by_user_id' => null,
            'portfolio_id' => null,
            'collection' => 'homepage',
            'disk' => 'public',
            'path' => 'media/'.fake()->unique()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'width' => 1200,
            'height' => 800,
            'title_en' => 'Media image',
            'title_ar' => 'صورة وسائط',
            'alt_text_en' => 'Property exterior',
            'alt_text_ar' => 'واجهة العقار',
            'visibility' => 'public',
            ...$overrides,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function uploadPayload(array $overrides = []): array
    {
        return [
            'collection' => 'properties',
            'title_en' => 'Property image',
            'title_ar' => 'صورة العقار',
            'alt_text_en' => 'Property building exterior',
            'alt_text_ar' => 'واجهة مبنى العقار',
            'visibility' => 'public',
            'file' => UploadedFile::fake()->image('property.jpg', 120, 80),
            ...$overrides,
        ];
    }

    /** @param array<string, mixed> $overrides */
    private function updatePayload(array $overrides = []): array
    {
        return [
            'collection' => 'properties',
            'title_en' => 'Updated property image',
            'title_ar' => 'صورة العقار المحدثة',
            'alt_text_en' => 'Updated property building exterior',
            'alt_text_ar' => 'واجهة مبنى العقار المحدثة',
            'visibility' => 'public',
            ...$overrides,
        ];
    }
}
