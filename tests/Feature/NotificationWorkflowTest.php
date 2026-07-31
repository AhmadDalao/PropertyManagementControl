<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Modules\Documents\Actions\ManageDocuments;
use App\Modules\Notifications\Notifications\MaintenanceActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_tenant_submission_notifies_only_responsible_operators(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $unassignedManager = $this->createUserWithRole('property_manager', $portfolio);
        $superadmin = $this->createUserWithRole('superadmin');
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $this->assignManagerToAsset($manager, $asset);
        $this->createLease($portfolio, $tenant, $asset, $owner);

        $this->actingAs($tenantUser)
            ->post(route('maintenance-requests.store'), [
                'asset_id' => $asset->id,
                'category' => 'plumbing',
                'priority' => 'high',
                'title' => 'Water under kitchen sink',
                'description' => 'A steady leak is reaching the cabinet floor.',
            ])
            ->assertRedirect();

        foreach ([$owner, $manager, $superadmin] as $recipient) {
            $notification = $recipient->notifications()->sole();
            $this->assertSame('maintenance_created', $notification->data['event']);
            $this->assertStringContainsString(
                'New maintenance request',
                $notification->data['title_en'],
            );
            $this->assertStringContainsString(
                'طلب صيانة جديد',
                $notification->data['title_ar'],
            );
        }

        $this->assertSame(0, $unassignedManager->notifications()->count());
        $this->assertSame(0, $tenantUser->notifications()->count());
    }

    public function test_management_resolution_and_tenant_response_notify_each_side(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $superadmin = $this->createUserWithRole('superadmin');
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $this->assignManagerToAsset($manager, $asset);
        $request = $this->maintenanceRequest(
            $portfolio->id,
            $asset->id,
            $tenant->id,
            $tenantUser->id,
            $manager->id,
        );

        $this->actingAs($manager)
            ->put(route('maintenance-requests.update', $request), [
                'assigned_to_user_id' => $manager->id,
                'priority' => 'high',
                'status' => 'resolved',
                'resolution_summary' => 'Replaced the failed valve and tested the line.',
                'comment' => 'The repair is complete.',
                'is_public_comment' => true,
            ])
            ->assertRedirect(route('maintenance-requests.show', $request));

        $tenantNotification = $tenantUser->notifications()->sole();
        $this->assertSame(
            'maintenance_resolved',
            $tenantNotification->data['event'],
        );

        $this->actingAs($tenantUser)
            ->post(route('maintenance-requests.resolution-response.store', $request), [
                'outcome' => 'reopen',
                'note' => 'The leak started again after the technician left.',
            ])
            ->assertRedirect(route('maintenance-requests.show', $request));

        foreach ([$owner, $manager, $superadmin] as $recipient) {
            $events = $recipient->notifications()
                ->get()
                ->pluck('data')
                ->pluck('event')
                ->all();
            $this->assertContains('maintenance_reopened', $events);
        }

        $this->assertSame('open', $request->fresh()->status);
    }

    public function test_notification_inbox_is_localized_and_user_scoped(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $otherOwner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $request = $this->maintenanceRequest(
            $portfolio->id,
            $asset->id,
            $tenant->id,
            $tenantUser->id,
            $owner->id,
        );

        $owner->notify(new MaintenanceActivityNotification(
            'maintenance_created',
            $request,
            $tenantUser,
        ));
        $otherOwner->notify(new MaintenanceActivityNotification(
            'maintenance_created',
            $request,
            $tenantUser,
        ));
        $ownerNotification = $owner->notifications()->sole();
        $otherNotification = $otherOwner->notifications()->sole();

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('notifications.index', ['status' => 'unread']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/notifications/index')
                ->where('filters.status', 'unread')
                ->where('counts.all', 1)
                ->where('counts.unread', 1)
                ->where('notificationItems.total', 1)
                ->where('notificationSummary.unread_count', 1)
                ->where(
                    'notificationItems.data.0.title',
                    fn (string $title): bool => str_contains($title, 'طلب صيانة جديد'),
                ));

        $this->actingAs($owner)
            ->post(route('notifications.read', $otherNotification))
            ->assertNotFound();

        $this->actingAs($owner)
            ->post(route('notifications.read', $ownerNotification))
            ->assertRedirect(route('maintenance-requests.show', $request));

        $this->assertNotNull($ownerNotification->fresh()->read_at);
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_payment_lease_and_document_events_reach_only_affected_accounts(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $this->assignManagerToAsset($manager, $asset);
        $lease = $this->createLease($portfolio, $tenant, $asset, $manager);
        $unrelatedOwner = $this->createUserWithRole('owner', $this->createPortfolio());

        $this->actingAs($manager)
            ->post(route('payments.store'), [
                'lease_id' => $lease->id,
                'type' => 'rent',
                'method' => 'bank_transfer',
                'status' => 'posted',
                'reference' => 'PAY-NOTIFY-001',
                'received_on' => now()->toDateString(),
                'amount' => 750,
            ])
            ->assertRedirect();

        $payment = Payment::query()->where('reference', 'PAY-NOTIFY-001')->firstOrFail();

        foreach ([$tenantUser, $owner] as $recipient) {
            $notification = $recipient->notifications()->sole();
            $this->assertSame('payment_posted', $notification->data['event']);
            $this->assertSame('payment', $notification->data['resource_type']);
            $this->assertSame($manager->id, $notification->data['actor_user_id']);
        }

        $this->assertSame(0, $manager->notifications()->count());
        $this->assertSame(0, $unrelatedOwner->notifications()->count());

        $this->actingAs($manager)
            ->put(route('payments.update', $payment), [
                'status' => 'pending',
                'notes' => 'Bank confirmation requires another review.',
            ])
            ->assertRedirect(route('payments.show', $payment));

        $this->actingAs($manager)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('payments.show', $payment));

        $events = $tenantUser->notifications()
            ->get()
            ->pluck('data')
            ->pluck('event')
            ->all();
        $this->assertEqualsCanonicalizing([
            'payment_posted',
            'payment_reversed',
            'payment_voided',
        ], $events);

        $secondAsset = $this->createAsset($portfolio);
        $this->actingAs($owner)
            ->post(route('leases.store'), $this->leasePayload(
                $portfolio->id,
                $tenant->id,
                $secondAsset->id,
            ))
            ->assertRedirect();
        $newLease = $tenant->leases()->where('leaseable_id', $secondAsset->id)->sole();
        $this->assertTrue($tenantUser->notifications()
            ->get()
            ->pluck('data')
            ->contains(fn (array $data): bool => $data['event'] === 'lease_activated'));

        $document = app(ManageDocuments::class)->create($owner, [
            'documentable_type' => 'lease',
            'documentable_id' => $newLease->id,
            'type' => 'signed_contract',
            'title_en' => "Signed contract {$newLease->code}",
            'title_ar' => "العقد الموقع {$newLease->code}",
            'is_public' => true,
            'file' => $this->fakePdf('signed-contract.pdf'),
        ]);
        $documentNotification = $tenantUser->notifications()
            ->get()
            ->first(fn ($notification): bool => $notification->data['event'] === 'document_available');
        $this->assertNotNull($documentNotification);
        $this->assertSame('document_available', $documentNotification->data['event']);
        $this->assertSame($document->id, $documentNotification->data['resource_id']);

        $this->actingAs($tenantUser)
            ->withSession(['locale' => 'ar'])
            ->get(route('notifications.index', [
                'type' => 'payment',
                'search' => 'PAY-NOTIFY-001',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.type', 'payment')
                ->where('filters.search', 'PAY-NOTIFY-001')
                ->where('counts.all', 3)
                ->where('typeCounts.payment', 3)
                ->where('notificationItems.total', 3)
                ->where('notificationItems.data.0.resource_label', 'المدفوعات'));
    }

    private function maintenanceRequest(
        int $portfolioId,
        int $assetId,
        int $tenantProfileId,
        int $submittedBy,
        int $assignedTo,
    ): MaintenanceRequest {
        return MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolioId,
            'asset_id' => $assetId,
            'tenant_profile_id' => $tenantProfileId,
            'submitted_by_user_id' => $submittedBy,
            'assigned_to_user_id' => $assignedTo,
            'category' => 'plumbing',
            'priority' => 'high',
            'status' => 'open',
            'title' => 'Kitchen water leak',
            'description' => 'Water is leaking below the kitchen sink.',
            'requested_at' => now(),
            'due_at' => now()->addDay(),
        ]);
    }

    /** @return array<string, mixed> */
    private function leasePayload(int $portfolioId, int $tenantId, int $assetId): array
    {
        return [
            'portfolio_id' => $portfolioId,
            'tenant_profile_id' => $tenantId,
            'asset_id' => $assetId,
            'status' => 'active',
            'payment_frequency' => 'monthly',
            'started_at' => now()->startOfMonth()->toDateString(),
            'ends_at' => now()->startOfMonth()->addYear()->subDay()->toDateString(),
            'signed_at' => null,
            'rent_amount' => 2000,
            'deposit_amount' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'currency' => 'SAR',
            'billing_day' => 1,
            'renewal_notice_days' => 30,
            'terms_en' => null,
            'terms_ar' => null,
            'notes' => null,
        ];
    }
}
