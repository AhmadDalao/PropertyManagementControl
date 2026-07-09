<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TenantManagementWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_tenant_workspace_exposes_lifecycle_insights(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser, [
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null,
            'address' => null,
        ]);
        $leasedTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
            [
                'profile_type' => 'company',
                'company_name' => 'Tenant Co',
                'emergency_contact_name' => 'Emergency Contact',
                'emergency_contact_phone' => '+966500111222',
                'address' => 'Riyadh',
            ],
        );

        $this->createLease($portfolio, $leasedTenant, $this->createAsset($portfolio), $owner);

        $this->actingAs($owner)
            ->get(route('tenants.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/tenants/index')
                ->where('tenantInsights.total', 2)
                ->where('tenantInsights.companies', 1)
                ->where('tenantInsights.without_active_lease', 1)
                ->where('tenantInsights.missing_emergency', 1)
                ->where('tenantInsights.missing_address', 1)
                ->has('tenants.data', 2)
                ->where('tenants.data.0.leases_count', fn ($value) => is_numeric($value))
            );

        $this->assertSame('active', $tenant->fresh()->status);
    }

    public function test_tenant_create_and_update_sync_user_status_and_profile_details(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->post(route('tenants.store'), [
                'name' => 'Blocked Tenant',
                'email' => 'blocked-tenant@example.test',
                'phone' => '+966500333444',
                'preferred_locale' => 'ar',
                'password' => 'temporary-password',
                'profile_type' => 'company',
                'national_id' => 'TEN-100',
                'company_name' => 'Blocked Tenant LLC',
                'emergency_contact_name' => 'Emergency Person',
                'emergency_contact_phone' => '+966500555666',
                'address' => 'Jeddah',
                'notes' => 'Needs manual onboarding review.',
                'status' => 'blocked',
            ])
            ->assertRedirect(route('tenants.index'));

        $tenant = \App\Models\TenantProfile::query()
            ->where('national_id', 'TEN-100')
            ->with('user')
            ->firstOrFail();

        $this->assertSame('blocked', $tenant->status);
        $this->assertSame('suspended', $tenant->user?->status);
        $this->assertSame('Blocked Tenant LLC', $tenant->company_name);
        $this->assertSame('Emergency Person', $tenant->emergency_contact_name);

        $this->actingAs($owner)
            ->put(route('tenants.update', $tenant), [
                'name' => 'Active Tenant',
                'phone' => '+966500777888',
                'preferred_locale' => 'en',
                'profile_type' => 'individual',
                'national_id' => 'TEN-100',
                'company_name' => '',
                'emergency_contact_name' => 'Updated Emergency',
                'emergency_contact_phone' => '+966500999000',
                'address' => 'Riyadh',
                'notes' => 'Ready for lease.',
                'status' => 'active',
            ])
            ->assertRedirect(route('tenants.index'));

        $tenant->refresh()->load('user');

        $this->assertSame('active', $tenant->status);
        $this->assertSame('active', $tenant->user?->status);
        $this->assertSame('Active Tenant', $tenant->user?->name);
        $this->assertSame('Ready for lease.', $tenant->notes);
    }
}
