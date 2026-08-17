<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PortalAccessHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_open_an_arabic_tenant_handoff_page_and_generate_a_scoped_link(): void
    {
        $portfolio = $this->createPortfolio([
            'name_en' => 'North Portfolio',
            'name_ar' => 'محفظة الشمال',
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, [
            'name' => 'Tenant Access',
            'preferred_locale' => 'ar',
            'force_password_reset' => true,
        ]);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);

        $this->actingAs($owner)
            ->get(route('users.show', $tenantUser))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/show')
                ->missing('detailPage.decisionCards')
                ->where('detailPage.workflow.actions.0.href', route('users.portal-access.show', $tenantUser))
                ->where('detailPage.workflow.actions.0.label', 'Portal access')
                ->where('detailPage.workflow.actions.0.variant', 'primary'));
        $this->actingAs($owner)
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/tenants/show')
                ->where('detailPage.header.actions', fn ($actions): bool => collect($actions)
                    ->contains(fn (array $action): bool => $action['href'] === route('users.portal-access.show', [
                        'user' => $tenantUser,
                        'origin' => 'tenant',
                    ]))));

        $this->actingAs($owner)
            ->get(route('users.portal-access.show', [
                'user' => $tenantUser,
                'origin' => 'tenant',
                'locale' => 'ar',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/portal-access')
                ->where('portalAccess.header.title', 'صلاحية بوابة Tenant Access')
                ->where('portalAccess.header.backHref', route('tenants.show', $tenant))
                ->where('portalAccess.account.email', $tenantUser->email)
                ->where('portalAccess.account.portfolio', 'محفظة الشمال')
                ->where('portalAccess.account.password_change_required', true)
                ->where('portalAccess.canGenerate', true)
                ->where('portalAccess.expiresInMinutes', 60));

        $response = $this->actingAs($owner)
            ->postJson(route('users.portal-access.store', $tenantUser))
            ->assertOk()
            ->assertJsonPath('expires_in_minutes', 60);

        $token = $this->tokenFromUrl((string) $response->json('url'));
        $this->assertTrue(Password::broker()->tokenExists($tenantUser, $token));
        $this->assertStringContainsString('locale=ar', (string) $response->json('url'));

        $activity = Activity::query()
            ->where('event', 'portal_access_link_created')
            ->sole();
        $this->assertTrue($activity->causer->is($owner));
        $this->assertTrue($activity->subject->is($tenantUser));
        $this->assertSame('manual_secure_link', $activity->properties->get('delivery'));
        $this->assertStringNotContainsString($token, $activity->properties->toJson());
    }

    public function test_generating_a_new_link_revokes_the_previous_link_and_completes_password_setup(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio, [
            'force_password_reset' => true,
            'password' => Hash::make('temporary-secret'),
        ]);
        $this->createTenantProfile($portfolio, $tenant);

        $first = $this->actingAs($owner)
            ->postJson(route('users.portal-access.store', $tenant))
            ->assertOk();
        $firstToken = $this->tokenFromUrl((string) $first->json('url'));

        $second = $this->actingAs($owner)
            ->postJson(route('users.portal-access.store', $tenant))
            ->assertOk();
        $secondToken = $this->tokenFromUrl((string) $second->json('url'));

        $this->assertNotSame($firstToken, $secondToken);
        $this->assertFalse(Password::broker()->tokenExists($tenant, $firstToken));
        $this->assertTrue(Password::broker()->tokenExists($tenant, $secondToken));

        $this->app['auth']->guard()->logout();
        $this->post(route('password.update'), [
            'token' => $secondToken,
            'email' => $tenant->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect(route('login'));

        $tenant->refresh();
        $this->assertFalse($tenant->force_password_reset);
        $this->assertTrue(Hash::check('new-secure-password', $tenant->password));
        $this->assertFalse(Password::broker()->tokenExists($tenant, $secondToken));
    }

    public function test_manager_can_only_generate_links_for_tenants_inside_assigned_properties(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $assignedAsset = $this->createAsset($portfolio);
        $hiddenAsset = $this->createAsset($portfolio);
        $this->assignManagerToAsset($manager, $assignedAsset);

        $visibleUser = $this->createUserWithRole('tenant', $portfolio);
        $visibleTenant = $this->createTenantProfile($portfolio, $visibleUser);
        $this->createLease($portfolio, $visibleTenant, $assignedAsset, $manager);

        $hiddenUser = $this->createUserWithRole('tenant', $portfolio);
        $hiddenTenant = $this->createTenantProfile($portfolio, $hiddenUser);
        $this->createLease($portfolio, $hiddenTenant, $hiddenAsset);

        $this->actingAs($manager)
            ->get(route('users.portal-access.show', $visibleUser))
            ->assertOk();
        $this->actingAs($manager)
            ->postJson(route('users.portal-access.store', $visibleUser))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('users.portal-access.show', $hiddenUser))
            ->assertForbidden();
        $this->actingAs($manager)
            ->postJson(route('users.portal-access.store', $hiddenUser))
            ->assertForbidden();
    }

    public function test_inactive_accounts_and_tenant_actors_cannot_generate_links(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $inactive = $this->createUserWithRole('tenant', $portfolio, [
            'status' => 'inactive',
        ]);
        $this->createTenantProfile($portfolio, $inactive);
        $tenantActor = $this->createUserWithRole('tenant', $portfolio);
        $this->createTenantProfile($portfolio, $tenantActor);

        $this->actingAs($owner)
            ->postJson(route('users.portal-access.store', $inactive))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('account');

        $this->actingAs($tenantActor)
            ->get(route('users.portal-access.show', $inactive))
            ->assertForbidden();
        $this->actingAs($tenantActor)
            ->postJson(route('users.portal-access.store', $inactive))
            ->assertForbidden();

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $inactive->email,
        ]);
    }

    private function tokenFromUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $token = urldecode((string) basename($path));

        $this->assertNotSame('', $token);

        return $token;
    }
}
