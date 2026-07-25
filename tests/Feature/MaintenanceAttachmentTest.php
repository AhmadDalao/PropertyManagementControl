<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceAttachmentOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaintenanceAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_tenant_creates_request_with_private_photos_and_authorized_users_can_view_them(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $this->createLease($portfolio, $tenant, $asset, $owner);

        $response = $this->actingAs($tenantUser)
            ->post(route('maintenance-requests.store'), [
                'asset_id' => $asset->id,
                'category' => 'plumbing',
                'priority' => 'high',
                'title' => 'Visible pipe leak',
                'description' => 'Water is collecting below the kitchen pipe.',
                'photos' => [
                    UploadedFile::fake()->image('leak-wide.jpg', 640, 360),
                    UploadedFile::fake()->image('leak-close.png', 320, 320),
                ],
            ]);

        $requestItem = MaintenanceRequest::query()
            ->where('title', 'Visible pipe leak')
            ->firstOrFail();
        $response->assertRedirect(route('maintenance-requests.show', $requestItem));
        $this->assertCount(2, $requestItem->attachments);

        $attachment = $requestItem->attachments()->oldest()->firstOrFail();
        $this->assertSame($portfolio->id, $attachment->portfolio_id);
        $this->assertSame($tenantUser->id, $attachment->uploaded_by_user_id);
        $this->assertSame('image/jpeg', $attachment->mime_type);
        $this->assertSame(640, $attachment->width);
        $this->assertSame(360, $attachment->height);
        Storage::disk('local')->assertExists($attachment->file_path);

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.show', $requestItem))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.stats', fn ($stats): bool => collect($stats)
                    ->contains(fn ($stat): bool => $stat['label'] === 'Evidence photos'
                        && $stat['value'] === 2))
                ->has('detailPage.documents', 2)
                ->where('detailPage.documents.0.badge', 'Photo')
                ->where(
                    'detailPage.documents.0.thumbnail',
                    route('maintenance-requests.attachments.show', [$requestItem, $requestItem->attachments()->latest()->firstOrFail()]),
                )
                ->where('detailPage.header.actions.0.label', 'Add photos'));

        $this->actingAs($owner)
            ->get(route('maintenance-requests.attachments.show', [$requestItem, $attachment]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    public function test_invalid_or_excess_initial_photos_roll_back_the_entire_request(): void
    {
        [$tenantUser, $asset] = $this->tenantLeaseFixture();

        $this->actingAs($tenantUser)
            ->post(route('maintenance-requests.store'), [
                ...$this->tenantPayload($asset->id, 'Spoofed evidence'),
                'photos' => [
                    UploadedFile::fake()->create('not-an-image.jpg', 20, 'image/jpeg'),
                ],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseMissing('maintenance_requests', ['title' => 'Spoofed evidence']);
        $this->assertDatabaseCount('maintenance_attachments', 0);

        $photos = [];

        for ($index = 0; $index <= MaintenanceAttachmentOptions::MAX_FILES_PER_UPLOAD; $index++) {
            $photos[] = UploadedFile::fake()->image("photo-{$index}.jpg");
        }

        $this->actingAs($tenantUser)
            ->post(route('maintenance-requests.store'), [
                ...$this->tenantPayload($asset->id, 'Too many photos'),
                'photos' => $photos,
            ])
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseMissing('maintenance_requests', ['title' => 'Too many photos']);
        $this->assertDatabaseCount('maintenance_attachments', 0);
    }

    public function test_later_upload_is_private_scoped_and_rejects_mismatched_nested_records(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $otherTenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $otherTenant = $this->createTenantProfile($portfolio, $otherTenantUser);
        $asset = $this->createAsset($portfolio);
        $requestItem = $this->maintenanceRecord(
            $portfolio->id,
            $asset->id,
            $tenant->id,
            $tenantUser->id,
            'Tenant evidence',
        );
        $otherRequest = $this->maintenanceRecord(
            $portfolio->id,
            $asset->id,
            $otherTenant->id,
            $otherTenantUser->id,
            'Other evidence',
        );

        $this->actingAs($tenantUser)
            ->get(route('maintenance-requests.attachments.create', $requestItem))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-form')
                ->where('formPage.title', 'Add photos')
                ->where('formPage.fields.0.name', 'photos')
                ->where('formPage.fields.0.multiple', true));

        $this->actingAs($tenantUser)
            ->post(route('maintenance-requests.attachments.store', $requestItem), [
                'photos' => [UploadedFile::fake()->image('evidence.webp', 480, 320)],
            ])
            ->assertRedirect(route('maintenance-requests.show', [$requestItem, 'tab' => 'documents']));

        $attachment = $requestItem->attachments()->firstOrFail();

        $this->actingAs($otherTenantUser)
            ->get(route('maintenance-requests.attachments.show', [$requestItem, $attachment]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('maintenance-requests.attachments.show', [$otherRequest, $attachment]))
            ->assertNotFound();

        $foreignOwner = $this->createUserWithRole('owner', $this->createPortfolio());
        $this->actingAs($foreignOwner)
            ->post(route('maintenance-requests.attachments.store', $requestItem))
            ->assertForbidden();
        $this->actingAs($foreignOwner)
            ->post(route('maintenance-requests.attachments.store', $requestItem), [
                'photos' => [UploadedFile::fake()->image('foreign.jpg')],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('maintenance_attachments', 1);
    }

    public function test_request_wide_photo_limit_blocks_additional_storage(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $requestItem = $this->maintenanceRecord(
            $portfolio->id,
            $asset->id,
            $tenant->id,
            $tenantUser->id,
            'Full evidence',
        );

        for ($index = 0; $index < MaintenanceAttachmentOptions::MAX_FILES_PER_REQUEST; $index++) {
            $requestItem->attachments()->create([
                'portfolio_id' => $portfolio->id,
                'uploaded_by_user_id' => $owner->id,
                'disk' => 'local',
                'file_path' => "existing/photo-{$index}.jpg",
                'original_name' => "photo-{$index}.jpg",
                'mime_type' => 'image/jpeg',
                'file_size' => 100,
                'width' => 100,
                'height' => 100,
            ]);
        }

        $this->actingAs($owner)
            ->post(route('maintenance-requests.attachments.store', $requestItem), [
                'photos' => [UploadedFile::fake()->image('extra.jpg')],
            ])
            ->assertSessionHasErrors('photos');

        $this->assertSame(
            MaintenanceAttachmentOptions::MAX_FILES_PER_REQUEST,
            MaintenanceAttachment::query()->where('maintenance_request_id', $requestItem->id)->count(),
        );
        Storage::disk('local')->assertMissing(
            "maintenance/attachments/{$portfolio->id}/{$requestItem->id}",
        );
    }

    public function test_arabic_forms_expose_direct_private_photo_wording(): void
    {
        [$tenantUser, $asset] = $this->tenantLeaseFixture();

        $this->actingAs($tenantUser)
            ->withSession(['locale' => 'ar'])
            ->get(route('maintenance-requests.create', ['asset_id' => $asset->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('formPage.fields', fn ($fields): bool => collect($fields)
                    ->contains(fn ($field): bool => $field['name'] === 'photos'
                        && $field['label'] === 'صور توثيق العطل'
                        && $field['multiple'] === true)));
    }

    /** @return array{0:User,1:Asset} */
    private function tenantLeaseFixture(): array
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $this->createLease($portfolio, $tenant, $asset, $owner);

        return [$tenantUser, $asset];
    }

    /** @return array<string, mixed> */
    private function tenantPayload(int $assetId, string $title): array
    {
        return [
            'asset_id' => $assetId,
            'category' => 'general',
            'priority' => 'medium',
            'title' => $title,
            'description' => 'Clear maintenance description.',
        ];
    }

    private function maintenanceRecord(
        int $portfolioId,
        int $assetId,
        int $tenantId,
        int $submittedBy,
        string $title,
    ): MaintenanceRequest {
        return MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolioId,
            'asset_id' => $assetId,
            'tenant_profile_id' => $tenantId,
            'submitted_by_user_id' => $submittedBy,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'title' => $title,
            'description' => 'Maintenance evidence test.',
            'requested_at' => now(),
        ]);
    }
}
