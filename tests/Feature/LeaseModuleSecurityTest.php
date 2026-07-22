<?php

namespace Tests\Feature;

use App\Modules\Leases\Actions\ManageLeases;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class LeaseModuleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_create_form_only_offers_active_tenants_and_available_assets(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $activeTenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Available Tenant']),
        );
        $blockedProfile = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Blocked Profile']),
            ['status' => 'blocked'],
        );
        $inactiveAccount = $this->createUserWithRole('tenant', $portfolio, [
            'name' => 'Inactive Account',
            'status' => 'inactive',
        ]);
        $inactiveTenant = $this->createTenantProfile($portfolio, $inactiveAccount);
        $availableAsset = $this->createAsset($portfolio, ['title_en' => 'Available Unit']);
        $occupiedAsset = $this->createAsset($portfolio, ['title_en' => 'Occupied Unit']);
        $this->createLease($portfolio, $activeTenant, $occupiedAsset, $owner);

        $this->actingAs($owner)
            ->get(route('leases.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-form')
                ->where('formPage.fields', function ($fields) use (
                    $activeTenant,
                    $blockedProfile,
                    $inactiveTenant,
                    $availableAsset,
                    $occupiedAsset,
                ): bool {
                    $fields = collect($fields);
                    $tenantOptions = collect($fields->firstWhere('name', 'tenant_profile_id')['options'] ?? []);
                    $assetOptions = collect($fields->firstWhere('name', 'asset_id')['options'] ?? []);
                    $statusOptions = collect($fields->firstWhere('name', 'status')['options'] ?? [])->pluck('value');

                    return $tenantOptions->contains('value', $activeTenant->id)
                        && ! $tenantOptions->contains('value', $blockedProfile->id)
                        && ! $tenantOptions->contains('value', $inactiveTenant->id)
                        && $assetOptions->contains('value', $availableAsset->id)
                        && ! $assetOptions->contains('value', $occupiedAsset->id)
                        && $statusOptions->all() === ['draft', 'active'];
                }));
    }

    public function test_superadmin_portfolio_selection_reloads_scoped_lease_options(): void
    {
        $firstPortfolio = $this->createPortfolio(['name_en' => 'First Portfolio']);
        $secondPortfolio = $this->createPortfolio(['name_en' => 'Second Portfolio']);
        $superadmin = $this->createUserWithRole('superadmin');
        $firstTenant = $this->createTenantProfile(
            $firstPortfolio,
            $this->createUserWithRole('tenant', $firstPortfolio, ['name' => 'First Tenant']),
        );
        $secondTenant = $this->createTenantProfile(
            $secondPortfolio,
            $this->createUserWithRole('tenant', $secondPortfolio, ['name' => 'Second Tenant']),
        );
        $firstAsset = $this->createAsset($firstPortfolio, ['title_en' => 'First Unit']);
        $secondAsset = $this->createAsset($secondPortfolio, ['title_en' => 'Second Unit']);

        $this->actingAs($superadmin)
            ->get(route('leases.create', ['portfolio_id' => $secondPortfolio->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.initialValues.portfolio_id', (string) $secondPortfolio->id)
                ->where('formPage.fields', function ($fields) use (
                    $firstTenant,
                    $secondTenant,
                    $firstAsset,
                    $secondAsset,
                ): bool {
                    $fields = collect($fields);
                    $portfolio = $fields->firstWhere('name', 'portfolio_id');
                    $tenantOptions = collect($fields->firstWhere('name', 'tenant_profile_id')['options'] ?? []);
                    $assetOptions = collect($fields->firstWhere('name', 'asset_id')['options'] ?? []);

                    return data_get($portfolio, 'reloadOnChange.queryKey') === 'portfolio_id'
                        && $tenantOptions->contains('value', $secondTenant->id)
                        && ! $tenantOptions->contains('value', $firstTenant->id)
                        && $assetOptions->contains('value', $secondAsset->id)
                        && ! $assetOptions->contains('value', $firstAsset->id);
                }));
    }

    public function test_superadmin_defaults_to_a_portfolio_that_can_create_a_lease(): void
    {
        $fullPortfolio = $this->createPortfolio(['name_en' => 'A Full Portfolio']);
        $readyPortfolio = $this->createPortfolio(['name_en' => 'B Ready Portfolio']);
        $superadmin = $this->createUserWithRole('superadmin');
        $fullOwner = $this->createUserWithRole('owner', $fullPortfolio);
        $fullTenant = $this->createTenantProfile(
            $fullPortfolio,
            $this->createUserWithRole('tenant', $fullPortfolio),
        );
        $fullAsset = $this->createAsset($fullPortfolio);
        $this->createLease($fullPortfolio, $fullTenant, $fullAsset, $fullOwner);
        $this->createTenantProfile(
            $readyPortfolio,
            $this->createUserWithRole('tenant', $readyPortfolio),
        );
        $readyAsset = $this->createAsset($readyPortfolio);

        $this->actingAs($superadmin)
            ->get(route('leases.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.initialValues.portfolio_id', (string) $readyPortfolio->id)
                ->where('formPage.initialValues.asset_id', (string) $readyAsset->id));
    }

    public function test_create_form_explains_when_a_portfolio_has_no_available_asset(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $asset = $this->createAsset($portfolio);
        $this->createLease($portfolio, $tenant, $asset, $owner);

        $this->actingAs($owner)
            ->get(route('leases.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.initialValues.asset_id', '')
                ->where('formPage.fields', fn ($fields): bool => collect($fields)
                    ->firstWhere('name', 'asset_id')['options'][0]['label']
                        === 'No available rentable assets in this portfolio'));
    }

    public function test_http_and_direct_actions_reject_invalid_lease_creation(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $asset = $this->createAsset($portfolio);
        $payload = $this->leasePayload($portfolio->id, $tenant->id, $asset->id, ['status' => 'expired']);

        $this->actingAs($owner)
            ->post(route('leases.store'), $payload)
            ->assertSessionHasErrors('status');
        $this->assertDatabaseCount('leases', 0);
        $this->assertValidationError(
            fn () => app(ManageLeases::class)->create($owner, $payload),
            'status',
        );

        $tenant->update(['status' => 'blocked']);
        $this->assertValidationError(
            fn () => app(ManageLeases::class)->create(
                $owner,
                $this->leasePayload($portfolio->id, $tenant->id, $asset->id),
            ),
            'tenant_profile_id',
        );
        $this->assertDatabaseCount('leases', 0);
    }

    public function test_inactive_tenant_account_and_portfolio_cannot_receive_a_lease(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, ['status' => 'inactive']);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);

        $this->assertValidationError(
            fn () => app(ManageLeases::class)->create(
                $owner,
                $this->leasePayload($portfolio->id, $tenant->id, $asset->id),
            ),
            'tenant_profile_id',
        );

        $portfolio->update(['status' => 'archived']);
        $tenantUser->update(['status' => 'active']);
        $superadmin = $this->createUserWithRole('superadmin');
        $this->assertValidationError(
            fn () => app(ManageLeases::class)->create(
                $superadmin,
                $this->leasePayload($portfolio->id, $tenant->id, $asset->id),
            ),
            'portfolio_id',
        );
        $this->assertDatabaseCount('leases', 0);
    }

    public function test_expired_and_terminated_leases_cannot_be_reopened(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $expired = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
            ['status' => 'expired'],
        );
        $terminated = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
            ['status' => 'terminated'],
        );

        foreach ([$expired, $terminated] as $lease) {
            $this->assertValidationError(
                fn () => app(ManageLeases::class)->update($owner, $lease, [
                    'status' => 'active',
                    'signed_at' => null,
                    'terms_en' => null,
                    'terms_ar' => null,
                    'notes' => null,
                ]),
                'status',
            );
        }

        $this->assertSame('expired', $expired->fresh()->status);
        $this->assertSame('terminated', $terminated->fresh()->status);
    }

    public function test_lease_list_minimizes_personal_data_and_normalizes_filters(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, [
                'name' => 'Private Tenant',
                'email' => 'private-tenant@example.test',
            ]),
        );
        $lease = $this->createLease($portfolio, $tenant, $this->createAsset($portfolio), $owner);

        $this->actingAs($owner)
            ->get(route('leases.index', [
                'search' => $lease->code,
                'status' => 'not-a-status',
                'payment_frequency' => 'weekly',
                'date_from' => '2026-99-99',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('leases.total', 1)
                ->where('leases.data.0.tenant_profile.user.name', 'Private Tenant')
                ->missing('leases.data.0.tenant_profile.user.email')
                ->missing('leases.data.0.notes')
                ->missing('leases.data.0.installments')
                ->where('filters.status', 'all')
                ->where('filters.payment_frequency', 'all')
                ->where('filters.date_from', ''));
    }

    public function test_arabic_lease_index_form_and_detail_are_translated(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, ['preferred_locale' => 'ar']);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio, ['name' => 'Arabic Lease Tenant']),
        );
        $lease = $this->createLease($portfolio, $tenant, $this->createAsset($portfolio), $owner);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('leases.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('counts.0.label', 'الكل')
                ->where('app.translations.leases.register_title', 'سجل العقود'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('leases.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.title', 'إنشاء عقد')
                ->where('formPage.submitLabel', 'إنشاء عقد')
                ->where('formPage.fields.0.label', 'المستأجر'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('leases.show', $lease))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.header.eyebrow', 'سجل العقد')
                ->where('detailPage.sections.0.title', 'العقد')
                ->where('detailPage.sections.1.title', 'المبالغ')
                ->where('detailPage.sections.1.tab', 'financial'));
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function leasePayload(int $portfolioId, int $tenantId, int $assetId, array $overrides = []): array
    {
        return array_merge([
            'portfolio_id' => $portfolioId,
            'tenant_profile_id' => $tenantId,
            'asset_id' => $assetId,
            'status' => 'active',
            'payment_frequency' => 'monthly',
            'started_at' => '2026-01-01',
            'ends_at' => '2026-12-31',
            'signed_at' => null,
            'rent_amount' => 2000,
            'deposit_amount' => 1000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'currency' => 'sar',
            'billing_day' => 1,
            'terms_en' => null,
            'terms_ar' => null,
            'notes' => null,
        ], $overrides);
    }

    private function assertValidationError(callable $action, string $field): void
    {
        try {
            $action();
            $this->fail("Expected a validation error for {$field}.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
