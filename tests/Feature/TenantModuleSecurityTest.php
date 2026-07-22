<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TenantModuleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_tenant_workspace_and_export_never_leak_another_portfolio(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $visible = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, [
                'name' => 'Visible Tenant',
                'email' => 'visible-tenant@example.test',
            ]),
            ['address' => 'Visible District'],
        );
        $hidden = $this->createTenantProfile(
            $foreignPortfolio,
            $this->createUserWithRole('tenant', $foreignPortfolio, [
                'name' => 'Hidden Tenant',
                'email' => 'hidden-tenant@example.test',
            ]),
            ['address' => 'Hidden District'],
        );

        $this->actingAs($owner)
            ->get(route('tenants.index', ['search' => 'Tenant', 'per_page' => 100]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/tenants/index')
                ->where('tenants.total', 1)
                ->where('tenants.data.0.id', $visible->id)
                ->missing('tenants.data.0.emergency_contact_name')
                ->missing('tenants.data.0.emergency_contact_phone')
                ->missing('tenants.data.0.address')
                ->missing('tenants.data.0.user.preferred_locale')
                ->where('tenants.data.0.missing_profile_fields', fn ($fields): bool => collect($fields)
                    ->contains('emergency_contact'))
                ->where('counts.0.label', 'All')
                ->where('filters.search', 'Tenant'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('tenants.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('counts.0.label', 'الكل'));

        $this->actingAs($owner)->get(route('tenants.show', $hidden))->assertForbidden();
        $this->actingAs($owner)->get(route('tenants.edit', $hidden))->assertForbidden();
        $this->actingAs($owner)
            ->put(route('tenants.update', $hidden), $this->tenantPayload([
                'email' => 'hidden-tenant@example.test',
            ]))
            ->assertForbidden();
        $this->actingAs($owner)->delete(route('tenants.destroy', $hidden))->assertForbidden();

        $export = $this->actingAs($owner)->get(route('exports.resource', [
            'resource' => 'tenants',
            'search' => 'Tenant',
        ]));
        $export->assertOk();
        $sheet = $this->xlsxWorksheetXml($export);
        $this->assertStringContainsString('Visible Tenant', $sheet);
        $this->assertStringNotContainsString('Hidden Tenant', $sheet);
    }

    public function test_owner_cannot_create_a_tenant_in_another_portfolio(): void
    {
        $portfolio = $this->createPortfolio();
        $foreignPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->post(route('tenants.store'), $this->tenantPayload([
                'portfolio_id' => $foreignPortfolio->id,
                'email' => 'cross-portfolio@example.test',
            ]))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'cross-portfolio@example.test']);
        $this->assertDatabaseCount('tenant_profiles', 0);
    }

    public function test_superadmin_must_choose_an_active_portfolio(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $inactivePortfolio = $this->createPortfolio(['status' => 'archived']);

        $this->actingAs($superadmin)
            ->post(route('tenants.store'), $this->tenantPayload([
                'portfolio_id' => null,
                'email' => 'missing-portfolio@example.test',
            ]))
            ->assertSessionHasErrors('portfolio_id');

        $this->actingAs($superadmin)
            ->post(route('tenants.store'), $this->tenantPayload([
                'portfolio_id' => $inactivePortfolio->id,
                'email' => 'inactive-portfolio@example.test',
            ]))
            ->assertSessionHasErrors('portfolio_id');

        $this->actingAs($superadmin)
            ->get(route('tenants.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.fields', fn ($fields): bool => collect($fields)
                    ->pipe(fn ($items): bool => collect(
                        $items->firstWhere('name', 'portfolio_id')['options'],
                    )->contains(
                        fn ($option): bool => (string) $option['value'] === (string) $inactivePortfolio->id,
                    ) === false)));

        $this->assertDatabaseMissing('users', ['email' => 'missing-portfolio@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'inactive-portfolio@example.test']);
    }

    public function test_tenant_options_are_strictly_validated(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->post(route('tenants.store'), $this->tenantPayload([
                'profile_type' => 'government_agency',
                'status' => 'deleted',
            ]))
            ->assertSessionHasErrors(['profile_type', 'status']);

        $this->assertDatabaseCount('tenant_profiles', 0);
    }

    public function test_updating_login_credentials_keeps_profile_and_account_synchronized(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, [
            'email' => 'tenant-before@example.test',
        ]);
        $tenantUser->assignRole('owner');
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);

        $this->actingAs($owner)
            ->put(route('tenants.update', $tenant), $this->tenantPayload([
                'name' => 'Tenant Updated',
                'email' => 'tenant-after@example.test',
                'password' => 'replacement-password',
                'preferred_locale' => 'ar',
                'status' => 'inactive',
            ]))
            ->assertRedirect(route('tenants.show', $tenant));

        $tenant->refresh()->load('user.roles');
        $this->assertSame($portfolio->id, $tenant->user?->portfolio_id);
        $this->assertSame('Tenant Updated', $tenant->user?->name);
        $this->assertSame('tenant-after@example.test', $tenant->user?->email);
        $this->assertSame('ar', $tenant->user?->preferred_locale);
        $this->assertSame('inactive', $tenant->status);
        $this->assertSame('inactive', $tenant->user?->status);
        $this->assertTrue((bool) $tenant->user?->force_password_reset);
        $this->assertTrue(Hash::check('replacement-password', (string) $tenant->user?->password));
        $this->assertTrue($tenant->user?->hasRole('tenant') ?? false);
        $this->assertSame(['tenant'], $tenant->user?->getRoleNames()->sort()->values()->all());
    }

    public function test_tenant_credential_changes_revoke_sessions_tokens_and_resets(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, [
            'email' => 'tenant-old@example.test',
            'email_verified_at' => now(),
            'remember_token' => 'tenant-remember-token',
        ]);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $this->insertSession('tenant-owner-session', $owner);
        $this->insertSession('tenant-portal-session', $tenantUser);
        DB::table('password_reset_tokens')->insert([
            'email' => $tenantUser->email,
            'token' => 'tenant-reset-token',
            'created_at' => now(),
        ]);

        $this->actingAs($owner)
            ->put(route('tenants.update', $tenant), $this->tenantPayload([
                'email' => 'TENANT-NEW@example.test',
                'password' => 'replacement-password',
            ]))
            ->assertRedirect(route('tenants.show', $tenant));

        $tenantUser->refresh();
        $this->assertSame('tenant-new@example.test', $tenantUser->email);
        $this->assertNull($tenantUser->email_verified_at);
        $this->assertNotSame('tenant-remember-token', $tenantUser->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'tenant-portal-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'tenant-owner-session', 'user_id' => $owner->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'tenant-old@example.test']);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('tenants.edit', $tenant))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.fields', fn ($fields): bool => collect($fields)
                    ->firstWhere('name', 'email')['help'] === 'يؤدي تغيير بريد الدخول أو كلمة المرور إلى إنهاء جميع جلسات هذا المستأجر النشطة.'));
    }

    public function test_tenant_form_cannot_demote_a_portfolio_owner_account(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $portfolio->update(['owner_user_id' => $owner->id]);
        $tenant = $this->createTenantProfile($portfolio, $owner);
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->put(route('tenants.update', $tenant), $this->tenantPayload([
                'email' => $owner->email,
            ]))
            ->assertSessionHasErrors('role');

        $this->assertTrue($owner->fresh()->hasRole('owner'));
        $this->assertSame($owner->id, $portfolio->fresh()->owner_user_id);
    }

    public function test_active_lease_blocks_status_archive_and_delete_archive(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, [
            'email' => 'active-contract@example.test',
        ]);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $lease = $this->createLease($portfolio, $tenant, $this->createAsset($portfolio), $owner);

        foreach (['inactive', 'blocked'] as $status) {
            $this->actingAs($owner)
                ->put(route('tenants.update', $tenant), $this->tenantPayload([
                    'email' => 'active-contract@example.test',
                    'status' => $status,
                ]))
                ->assertSessionHasErrors('status');
        }

        $this->actingAs($owner)
            ->delete(route('tenants.destroy', $tenant))
            ->assertRedirect()
            ->assertSessionHas('error', trans('app.errors.tenant_has_active_lease'));

        $this->assertSame('active', $tenant->fresh()->status);
        $this->assertSame('active', $tenantUser->fresh()->status);

        $lease->update(['status' => 'terminated']);
        $tenantUser->update(['remember_token' => 'archive-remember-token']);
        $this->insertSession('tenant-archive-session', $tenantUser);

        $this->actingAs($owner)
            ->delete(route('tenants.destroy', $tenant))
            ->assertRedirect(route('tenants.index'));

        $this->assertSame('blocked', $tenant->fresh()->status);
        $tenantUser->refresh();
        $this->assertSame('suspended', $tenantUser->status);
        $this->assertNotSame('archive-remember-token', $tenantUser->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'tenant-archive-session']);
    }

    public function test_missing_login_can_be_recreated_without_rebuilding_the_profile(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $oldUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $oldUser);
        $oldUser->delete();
        $tenant->refresh();
        $this->assertNull($tenant->user_id);

        $this->actingAs($owner)
            ->put(route('tenants.update', $tenant), $this->tenantPayload([
                'email' => 'replacement-login@example.test',
                'password' => 'replacement-password',
            ]))
            ->assertRedirect(route('tenants.show', $tenant));

        $tenant->refresh()->load('user.roles');
        $this->assertNotNull($tenant->user_id);
        $this->assertSame('replacement-login@example.test', $tenant->user?->email);
        $this->assertTrue($tenant->user?->hasRole('tenant') ?? false);
    }

    public function test_tenant_cannot_open_management_workspace_and_detail_history_is_bounded(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        foreach (range(1, 12) as $number) {
            $this->createLease($portfolio, $tenant, $asset, $owner, [
                'code' => sprintf('TENANT-HISTORY-%02d', $number),
                'status' => 'expired',
                'started_at' => now()->subMonths($number + 12)->toDateString(),
                'ends_at' => now()->subMonths($number)->toDateString(),
            ], false);
        }

        $this->actingAs($tenantUser)->get(route('tenants.index'))->assertForbidden();
        $this->actingAs($tenantUser)->get(route('tenants.show', $tenant))->assertForbidden();

        $this->actingAs($owner)
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-show')
                ->has('detailPage.related.0.rows', 8));
    }

    public function test_financial_summary_uses_the_active_lease_allocations_only(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $historicalLease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
            [
                'status' => 'expired',
                'started_at' => now()->subYear()->startOfMonth()->toDateString(),
                'ends_at' => now()->subYear()->endOfMonth()->toDateString(),
                'currency' => 'USD',
            ],
        );
        $activeLease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
        );
        $activeLease->installments()->firstOrFail()->update(['amount_paid' => 500]);

        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $historicalLease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'TENANT-HISTORICAL-PAYMENT',
            'type' => 'rent',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => now()->subYear()->toDateString(),
            'amount' => 9000,
            'currency' => 'USD',
        ]);

        $this->actingAs($owner)
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.decisionCards.2.detail', '500.00 SAR allocated to the current contract')
                ->where('detailPage.stats.2.value', '500.00 SAR')
                ->where('detailPage.sections.1.items.2.value', '500.00 SAR'));
    }

    public function test_arabic_tenant_forms_and_details_are_translated(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Arabic Tenant']),
        );

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('tenants.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-form')
                ->where('app.locale', 'ar')
                ->where('formPage.title', 'إنشاء مستأجر')
                ->where('formPage.submitLabel', 'إنشاء مستأجر')
                ->where('formPage.fields.0.label', 'اسم المستأجر'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-show')
                ->where('detailPage.header.eyebrow', 'سجل المستأجر')
                ->where('detailPage.sections.0.title', 'الملف وبيانات الاتصال')
                ->where('detailPage.sections.1.tab', 'financial'));
    }

    /** @return array<string, mixed> */
    private function tenantPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tenant Person',
            'email' => 'tenant-person@example.test',
            'phone' => '+966500000001',
            'preferred_locale' => 'en',
            'password' => 'temporary-password',
            'profile_type' => 'individual',
            'national_id' => 'TENANT-ID-001',
            'company_name' => null,
            'emergency_contact_name' => 'Emergency Person',
            'emergency_contact_phone' => '+966500000002',
            'address' => 'Riyadh',
            'notes' => 'Tenant module test.',
            'status' => 'active',
        ], $overrides);
    }

    private function insertSession(string $id, User $user): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Tenant module security test',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);
    }
}
