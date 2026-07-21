<?php

namespace Tests\Feature;

use App\Models\AssetStakeholder;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserModuleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_manager_directory_export_and_search_only_expose_self_and_tenants(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Scoped Owner']);
        $manager = $this->createUserWithRole('property_manager', $portfolio, ['name' => 'Scoped Manager']);
        $peer = $this->createUserWithRole('property_manager', $portfolio, ['name' => 'Scoped Peer']);
        $tenant = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Scoped Tenant']);
        $foreign = $this->createUserWithRole('tenant', $foreignPortfolio, ['name' => 'Scoped Foreign']);

        $this->actingAs($manager)
            ->get(route('users.index', ['search' => 'Scoped', 'per_page' => 100]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->where('users.total', 2)
                ->where('users.data', fn ($users): bool => collect($users)
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->all() === collect([$manager->id, $tenant->id])->sort()->values()->all()));

        foreach ([$owner, $peer, $foreign] as $hidden) {
            $this->actingAs($manager)->get(route('users.show', $hidden))->assertForbidden();
            $this->actingAs($manager)->get(route('users.edit', $hidden))->assertForbidden();
        }

        $export = $this->actingAs($manager)->get(route('exports.resource', [
            'resource' => 'users',
            'search' => 'Scoped',
        ]));
        $export->assertOk();
        $sheet = $this->xlsxWorksheetXml($export);
        $this->assertStringContainsString('Scoped Manager', $sheet);
        $this->assertStringContainsString('Scoped Tenant', $sheet);
        $this->assertStringNotContainsString('Scoped Owner', $sheet);
        $this->assertStringNotContainsString('Scoped Peer', $sheet);
        $this->assertStringNotContainsString('Scoped Foreign', $sheet);

        $search = $this->actingAs($manager)->getJson(route('global-search', ['q' => 'Scoped']));
        $search->assertOk();
        $urls = collect($search->json('results'))->pluck('url')->all();
        $this->assertContains(route('profile.index'), $urls);
        $this->assertContains(route('users.show', $tenant), $urls);
        $this->assertNotContains(route('users.show', $owner), $urls);
        $this->assertNotContains(route('users.show', $peer), $urls);
        $this->assertNotContains(route('users.show', $foreign), $urls);
    }

    public function test_owner_directory_excludes_other_owner_and_foreign_accounts(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Directory Owner']);
        $otherOwner = $this->createUserWithRole('owner', $portfolio, ['name' => 'Directory Other Owner']);
        $manager = $this->createUserWithRole('property_manager', $portfolio, ['name' => 'Directory Manager']);
        $tenant = $this->createUserWithRole('tenant', $portfolio, ['name' => 'Directory Tenant']);
        $foreignTenant = $this->createUserWithRole('tenant', $foreignPortfolio, ['name' => 'Directory Foreign']);

        $this->actingAs($owner)
            ->get(route('users.index', ['search' => 'Directory', 'per_page' => 100]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.total', 3)
                ->where('users.data', fn ($users): bool => collect($users)
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->all() === collect([$owner->id, $manager->id, $tenant->id])->sort()->values()->all()));

        $this->actingAs($owner)->get(route('users.show', $otherOwner))->assertForbidden();
        $this->actingAs($owner)->get(route('users.show', $foreignTenant))->assertForbidden();
    }

    public function test_cross_module_user_links_follow_the_same_access_policy(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);
        $access = app(UserAccess::class);

        $this->assertNull($access->recordHref($manager, $owner));
        $this->assertSame(route('profile.index'), $access->recordHref($manager, $manager));
        $this->assertSame(route('users.show', $tenant), $access->recordHref($manager, $tenant));
        $this->assertSame(route('users.show', $manager), $access->recordHref($owner, $manager));
    }

    public function test_account_options_and_portfolio_boundaries_are_strictly_validated(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $inactivePortfolio = $this->createPortfolio(['status' => 'archived']);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($owner)
            ->post(route('users.store'), $this->userPayload([
                'preferred_locale' => 'fr',
                'status' => 'deleted',
                'role' => 'owner',
            ]))
            ->assertSessionHasErrors(['preferred_locale', 'status', 'role']);

        $this->actingAs($owner)
            ->post(route('users.store'), $this->userPayload([
                'portfolio_id' => $foreignPortfolio->id,
                'email' => 'foreign-user@example.test',
            ]))
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->post(route('users.store'), $this->userPayload([
                'portfolio_id' => $inactivePortfolio->id,
                'email' => 'inactive-user@example.test',
                'role' => 'property_manager',
            ]))
            ->assertSessionHasErrors('portfolio_id');

        $this->assertDatabaseMissing('users', ['email' => 'foreign-user@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'inactive-user@example.test']);
    }

    public function test_superadmin_creation_is_portfolio_free_and_owner_creation_claims_unowned_portfolio(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $portfolio = $this->createPortfolio();

        $this->actingAs($superadmin)
            ->post(route('users.store'), $this->userPayload([
                'portfolio_id' => $portfolio->id,
                'email' => 'system-admin@example.test',
                'role' => 'superadmin',
            ]))
            ->assertRedirect();

        $systemAdmin = User::query()->where('email', 'system-admin@example.test')->firstOrFail();
        $this->assertNull($systemAdmin->portfolio_id);
        $this->assertTrue($systemAdmin->hasRole('superadmin'));

        $this->actingAs($superadmin)
            ->post(route('users.store'), $this->userPayload([
                'portfolio_id' => $portfolio->id,
                'email' => 'portfolio-owner@example.test',
                'role' => 'owner',
            ]))
            ->assertRedirect();

        $portfolioOwner = User::query()->where('email', 'portfolio-owner@example.test')->firstOrFail();
        $this->assertSame($portfolioOwner->id, $portfolio->fresh()->owner_user_id);

        $this->actingAs($superadmin)
            ->post(route('users.store'), $this->userPayload([
                'portfolio_id' => $portfolio->id,
                'email' => 'duplicate-owner@example.test',
                'role' => 'owner',
            ]))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'duplicate-owner@example.test']);
    }

    public function test_active_lease_blocks_role_removal_and_account_suspension(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease($portfolio, $tenant, $this->createAsset($portfolio), $owner);

        $this->actingAs($owner)
            ->put(route('users.update', $tenantUser), $this->userUpdatePayload($tenantUser, [
                'role' => 'property_manager',
            ]))
            ->assertSessionHasErrors('role');

        $this->actingAs($owner)
            ->put(route('users.update', $tenantUser), $this->userUpdatePayload($tenantUser, [
                'status' => 'suspended',
            ]))
            ->assertSessionHasErrors('status');

        $this->actingAs($owner)
            ->put(route('users.update', $tenantUser), $this->userUpdatePayload($tenantUser, [
                'status' => 'inactive',
            ]))
            ->assertSessionHasErrors('status');

        $this->actingAs($owner)
            ->delete(route('users.destroy', $tenantUser))
            ->assertRedirect()
            ->assertSessionHas('error', trans('app.errors.user_has_active_lease'));

        $this->assertTrue($tenantUser->fresh()->hasRole('tenant'));
        $this->assertSame('active', $tenantUser->fresh()->status);

        $lease->update(['status' => 'terminated']);

        $this->actingAs($owner)
            ->put(route('users.update', $tenantUser), $this->userUpdatePayload($tenantUser, [
                'role' => 'property_manager',
            ]))
            ->assertRedirect(route('users.show', $tenantUser));

        $this->assertTrue($tenantUser->fresh()->hasRole('property_manager'));
        $this->assertSame('inactive', $tenant->fresh()->status);
    }

    public function test_portfolio_ownership_blocks_demotion_deactivation_and_suspension(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $secondaryOwner = $this->createUserWithRole('owner', $portfolio);
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->put(route('users.update', $owner), $this->userUpdatePayload($owner, [
                'status' => 'suspended',
                'role' => 'owner',
            ]))
            ->assertSessionHasErrors('status');

        $this->actingAs($superadmin)
            ->put(route('users.update', $owner), $this->userUpdatePayload($owner, [
                'role' => 'property_manager',
            ]))
            ->assertSessionHasErrors('role');

        $this->actingAs($superadmin)
            ->delete(route('users.destroy', $owner))
            ->assertRedirect()
            ->assertSessionHas('error', trans('app.errors.user_owns_portfolio'));

        $this->assertSame('active', $owner->fresh()->status);
        $this->assertTrue($owner->fresh()->hasRole('owner'));

        $this->actingAs($superadmin)
            ->put(route('users.update', $secondaryOwner), $this->userUpdatePayload($secondaryOwner, [
                'name' => 'Updated secondary owner',
            ]))
            ->assertRedirect(route('users.show', $secondaryOwner));

        $this->assertSame('Updated secondary owner', $secondaryOwner->fresh()->name);
        $this->assertSame($owner->id, $portfolio->fresh()->owner_user_id);
    }

    public function test_user_detail_payload_is_bounded_and_arabic_copy_is_resolved(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);

        foreach (range(1, 12) as $number) {
            $asset = $this->createAsset($portfolio, ['code' => "USR-ASSET-{$number}"]);
            AssetStakeholder::query()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
                'user_id' => $manager->id,
                'relationship_type' => 'manager',
                'is_primary' => true,
            ]);
            MaintenanceRequest::query()->create([
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
                'tenant_profile_id' => $tenant->id,
                'submitted_by_user_id' => $tenantUser->id,
                'assigned_to_user_id' => $manager->id,
                'category' => 'electrical',
                'priority' => 'medium',
                'status' => 'open',
                'title' => "User workload {$number}",
                'description' => 'Bounded user detail test.',
                'requested_at' => now()->subMinutes($number),
            ]);
        }

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('users.show', $manager))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-show')
                ->where('app.locale', 'ar')
                ->where('detailPage.header.eyebrow', 'حساب المستخدم')
                ->where('detailPage.sections.0.title', 'الحساب والنطاق')
                ->has('detailPage.related.0.rows', 8)
                ->has('detailPage.related.1.rows', 8));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('users.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.title', 'إنشاء مستخدم')
                ->where('formPage.fields', fn ($fields): bool => collect($fields)
                    ->pluck('label', 'name')
                    ->get('role') === 'الدور'));
    }

    /** @param array<string, mixed> $overrides */
    private function userPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Managed User',
            'email' => 'managed-user@example.test',
            'phone' => '+966500000999',
            'preferred_locale' => 'en',
            'status' => 'active',
            'password' => 'temporary-password',
            'role' => 'tenant',
        ], $overrides);
    }

    /** @param array<string, mixed> $overrides */
    private function userUpdatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'phone' => $user->phone,
            'preferred_locale' => $user->preferred_locale,
            'status' => $user->status,
            'password' => '',
            'role' => $user->getRoleNames()->first(),
        ], $overrides);
    }
}
