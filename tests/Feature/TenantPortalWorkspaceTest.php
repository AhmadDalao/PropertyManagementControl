<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TenantPortalWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_tenant_can_open_their_lease_payment_and_document_workspaces(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $profile = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Portal Unit']);
        $lease = $this->createLease($portfolio, $profile, $asset, $owner);
        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $profile->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'PORTAL-PAYMENT',
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 500,
            'currency' => 'SAR',
        ]);
        Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $payment->getMorphClass(),
            'documentable_id' => $payment->id,
            'type' => 'receipt',
            'title_en' => 'Portal receipt',
            'title_ar' => 'إيصال البوابة',
            'disk' => 'local',
            'file_path' => 'documents/portal-receipt.pdf',
            'original_name' => 'portal-receipt.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_public' => true,
        ]);

        $this->actingAs($tenantUser)
            ->get(route('tenant-portal.lease'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/tenant-portal/lease')
                ->where('lease.id', $lease->id)
                ->where('lease.asset.title_en', 'Portal Unit')
                ->has('schedule.data'));

        $this->actingAs($tenantUser)
            ->get(route('tenant-portal.payments'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/tenant-portal/payments')
                ->where('payments.total', 1)
                ->where('payments.data.0.reference', 'PORTAL-PAYMENT'));

        $this->actingAs($tenantUser)
            ->get(route('tenant-portal.documents'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/tenant-portal/documents')
                ->where('documents.total', 1)
                ->where('documents.data.0.title_en', 'Portal receipt'));
    }

    public function test_tenant_portal_never_exposes_another_tenants_records(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $profile = $this->createTenantProfile($portfolio, $tenant);
        $this->createLease($portfolio, $profile, $this->createAsset($portfolio), $owner);

        $otherTenant = $this->createUserWithRole('tenant', $portfolio);
        $otherProfile = $this->createTenantProfile($portfolio, $otherTenant);
        $otherLease = $this->createLease($portfolio, $otherProfile, $this->createAsset($portfolio), $owner);
        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $otherLease->id,
            'tenant_profile_id' => $otherProfile->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'FOREIGN-PORTAL-PAYMENT',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 900,
            'currency' => 'SAR',
        ]);

        $this->actingAs($tenant)
            ->get(route('tenant-portal.lease', ['lease_id' => $otherLease->id]))
            ->assertNotFound();
        $this->actingAs($tenant)
            ->get(route('tenant-portal.payments', ['lease_id' => $otherLease->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('payments.total', 0));
    }

    public function test_tenant_can_select_between_concurrent_and_historical_leases(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $profile = $this->createTenantProfile($portfolio, $tenant);
        $first = $this->createLease(
            $portfolio,
            $profile,
            $this->createAsset($portfolio, ['title_en' => 'First rental']),
            $owner,
            ['code' => 'LEASE-FIRST'],
        );
        $second = $this->createLease(
            $portfolio,
            $profile,
            $this->createAsset($portfolio, ['title_en' => 'Second rental']),
            $owner,
            ['code' => 'LEASE-SECOND'],
        );

        $this->actingAs($tenant)
            ->get(route('tenant-portal.lease', ['lease_id' => $second->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('lease.id', $second->id)
                ->where('lease.code', 'LEASE-SECOND')
                ->has('leases', 2)
                ->where('leases', fn ($leases) => collect($leases)->pluck('id')->contains($first->id)));
    }

    public function test_tenant_without_a_profile_sees_safe_empty_workspaces(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        $this->actingAs($tenant)
            ->get(route('tenant-portal.lease'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('lease', null)->has('leases', 0));

        $this->actingAs($tenant)
            ->get(route('tenant-portal.payments'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('payments.total', 0)->has('leases', 0));

        $this->actingAs($tenant)
            ->get(route('tenant-portal.documents'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('documents.total', 0)->has('leases', 0));
    }

    public function test_management_roles_cannot_enter_tenant_only_workspaces(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        foreach (['tenant-portal.lease', 'tenant-portal.payments', 'tenant-portal.documents'] as $route) {
            $this->actingAs($owner)->get(route($route))->assertForbidden();
        }
    }
}
