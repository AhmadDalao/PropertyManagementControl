<?php

namespace Tests\Feature;

use App\Models\CmsSection;
use App\Models\MediaFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
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
