<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_upload_and_update_global_website_media(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $superadmin = $this->createUserWithRole('superadmin');

        $response = $this->actingAs($superadmin)
            ->post(route('media-files.store'), [
                'portfolio_id' => null,
                'collection' => 'homepage',
                'title_en' => 'Hero',
                'title_ar' => 'البطل',
                'alt_text_en' => 'Hero image',
                'alt_text_ar' => 'صورة البطل',
                'visibility' => 'public',
                'file' => UploadedFile::fake()->image('hero.jpg'),
            ]);

        $media = MediaFile::query()->firstOrFail();

        $response->assertRedirect(route('media-files.show', $media));

        $this->assertNull($media->portfolio_id);
        Storage::disk('public')->assertExists($media->path);

        $this->actingAs($superadmin)
            ->put(route('media-files.update', $media), [
                'portfolio_id' => null,
                'collection' => 'landing',
                'title_en' => 'Updated Hero',
                'title_ar' => 'البطل المحدث',
                'alt_text_en' => 'Updated alt',
                'alt_text_ar' => 'وصف محدث',
                'visibility' => 'private',
            ])
            ->assertRedirect(route('media-files.show', $media));

        $media->refresh();

        $this->assertSame('landing', $media->collection);
        $this->assertSame('Updated Hero', $media->title_en);
        $this->assertSame('private', $media->visibility);
    }

    public function test_owner_cannot_create_or_update_global_media(): void
    {
        Storage::fake('public');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $media = MediaFile::query()->create([
            'uploaded_by_user_id' => null,
            'portfolio_id' => null,
            'collection' => 'global',
            'disk' => 'public',
            'path' => 'media/global.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 10,
            'title_en' => 'Global',
            'visibility' => 'public',
        ]);

        $this->actingAs($owner)
            ->post(route('media-files.store'), [
                'portfolio_id' => null,
                'collection' => 'bad',
                'title_en' => 'Bad',
                'title_ar' => 'غير صالح',
                'alt_text_en' => 'Invalid media',
                'alt_text_ar' => 'وسائط غير صالحة',
                'visibility' => 'public',
                'file' => UploadedFile::fake()->image('bad.jpg'),
            ])
            ->assertForbidden();

        $this->actingAs($owner)
            ->put(route('media-files.update', $media), [
                'portfolio_id' => null,
                'collection' => 'bad',
                'title_en' => 'Bad',
                'title_ar' => 'غير صالح',
                'visibility' => 'private',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_update_their_own_portfolio_media_but_not_move_it_global(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $storedImage = UploadedFile::fake()->image('unit.jpg');
        Storage::disk('public')->put('media/unit.jpg', (string) file_get_contents($storedImage->getRealPath()));
        $media = MediaFile::query()->create([
            'uploaded_by_user_id' => $owner->id,
            'portfolio_id' => $portfolio->id,
            'collection' => 'units',
            'disk' => 'public',
            'path' => 'media/unit.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 10,
            'title_en' => 'Unit',
            'visibility' => 'public',
        ]);

        $this->actingAs($owner)
            ->put(route('media-files.update', $media), [
                'portfolio_id' => $portfolio->id,
                'collection' => 'apartments',
                'title_en' => 'Unit Updated',
                'title_ar' => 'وحدة محدثة',
                'alt_text_en' => 'Unit photo',
                'alt_text_ar' => 'صورة الوحدة',
                'visibility' => 'private',
            ])
            ->assertRedirect(route('media-files.show', $media));

        $this->assertSame('apartments', $media->fresh()->collection);
        $this->assertSame('private', $media->fresh()->visibility);

        $this->actingAs($owner)
            ->put(route('media-files.update', $media), [
                'portfolio_id' => null,
                'collection' => 'global',
                'title_en' => 'Global now',
                'title_ar' => 'عام الآن',
                'alt_text_en' => 'Global media',
                'alt_text_ar' => 'وسائط عامة',
                'visibility' => 'public',
            ])
            ->assertForbidden();
    }
}
