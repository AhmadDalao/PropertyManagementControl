<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TenantLeaseOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_continue_a_new_tenant_into_a_prefilled_draft_lease(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $asset = $this->createAsset($portfolio);

        $this->actingAs($owner)
            ->get(route('tenants.create', [
                'next' => 'lease',
                'portfolio_id' => $portfolio->id,
                'asset_id' => $asset->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-form')
                ->where('formPage.title', 'Add tenant and start tenancy')
                ->where('formPage.submitLabel', 'Continue to lease')
                ->where('formPage.initialValues.next', 'lease')
                ->where('formPage.initialValues.asset_id', (string) $asset->id)
                ->where('formPage.fields', fn ($fields) => collect($fields)->contains(
                    fn (array $field): bool => ($field['name'] ?? null) === 'next'
                        && ($field['type'] ?? null) === 'hidden',
                ))
                ->where('formPage.fields', fn ($fields) => collect($fields)->contains(
                    fn (array $field): bool => ($field['name'] ?? null) === 'asset_id'
                        && ($field['type'] ?? null) === 'hidden',
                )));

        $response = $this->actingAs($owner)
            ->post(route('tenants.store'), [
                'portfolio_id' => $portfolio->id,
                'name' => 'Move In Tenant',
                'email' => 'move-in-tenant@example.test',
                'phone' => '+966500001111',
                'preferred_locale' => 'en',
                'password' => 'Safe-Password-123!',
                'profile_type' => 'individual',
                'status' => 'active',
                'next' => 'lease',
                'asset_id' => $asset->id,
            ]);

        $tenant = $portfolio->tenantProfiles()
            ->whereHas('user', fn ($query) => $query->where('email', 'move-in-tenant@example.test'))
            ->firstOrFail();
        $leaseUrl = route('leases.create', [
            'tenant_profile_id' => $tenant->id,
            'onboarding' => 1,
            'asset_id' => $asset->id,
        ]);

        $response
            ->assertRedirect($leaseUrl)
            ->assertSessionHas('success', 'Tenant created. Complete the draft lease next.');

        $this->actingAs($owner)
            ->get($leaseUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/leases/form')
                ->where('formPage.title', 'Set up the tenancy')
                ->where('formPage.submitLabel', 'Create draft lease')
                ->where('formPage.initialValues.tenant_profile_id', (string) $tenant->id)
                ->where('formPage.initialValues.asset_id', (string) $asset->id)
                ->where('formPage.initialValues.status', 'draft'));
    }

    public function test_asset_context_selects_the_correct_superadmin_portfolio_and_new_tenant_action(): void
    {
        $firstPortfolio = $this->createPortfolio(['name_en' => 'Alpha Portfolio']);
        $secondPortfolio = $this->createPortfolio(['name_en' => 'Zulu Portfolio']);
        $superadmin = $this->createUserWithRole('superadmin');
        $this->createAsset($firstPortfolio);
        $selectedAsset = $this->createAsset($secondPortfolio);

        $this->actingAs($superadmin)
            ->get(route('leases.create', ['asset_id' => $selectedAsset->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/leases/form')
                ->where('formPage.initialValues.portfolio_id', (string) $secondPortfolio->id)
                ->where('formPage.initialValues.asset_id', (string) $selectedAsset->id)
                ->where('formPage.headerActions.0.label', 'Add a new tenant')
                ->where(
                    'formPage.headerActions.0.href',
                    route('tenants.create', [
                        'next' => 'lease',
                        'portfolio_id' => $secondPortfolio->id,
                        'asset_id' => $selectedAsset->id,
                    ]),
                ));
    }

    public function test_unavailable_asset_context_never_silently_selects_another_rental(): void
    {
        $portfolio = $this->createPortfolio();
        $otherPortfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $availableAsset = $this->createAsset($portfolio);
        $unavailableAsset = $this->createAsset($otherPortfolio);

        $this->actingAs($owner)
            ->get(route('leases.create', ['asset_id' => $unavailableAsset->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.initialValues.portfolio_id', (string) $portfolio->id)
                ->where('formPage.initialValues.asset_id', '')
                ->where('formPage.headerActions.0.href', route('tenants.create', [
                    'next' => 'lease',
                    'portfolio_id' => $portfolio->id,
                ])));

        $this->actingAs($owner)
            ->post(route('tenants.store'), [
                'portfolio_id' => $portfolio->id,
                'name' => 'Blocked Context Tenant',
                'email' => 'blocked-context@example.test',
                'preferred_locale' => 'en',
                'password' => 'Safe-Password-123!',
                'profile_type' => 'individual',
                'status' => 'active',
                'next' => 'lease',
                'asset_id' => $unavailableAsset->id,
            ])
            ->assertSessionHasErrors('asset_id');

        $this->assertDatabaseMissing('users', ['email' => 'blocked-context@example.test']);
        $this->assertDatabaseHas('assets', ['id' => $availableAsset->id]);
    }

    public function test_lease_form_hides_tenant_onboarding_when_the_module_is_disabled(): void
    {
        $portfolio = $this->createPortfolio([
            'module_settings' => [
                'tenants' => false,
                'leases' => true,
            ],
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);
        $asset = $this->createAsset($portfolio);

        $this->actingAs($owner)
            ->get(route('leases.create', ['asset_id' => $asset->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.initialValues.asset_id', (string) $asset->id)
                ->where('formPage.headerActions', []));
    }

    public function test_tenant_continuation_rejects_untrusted_destinations(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->post(route('tenants.store'), [
                'portfolio_id' => $portfolio->id,
                'name' => 'Unsafe Redirect Tenant',
                'email' => 'unsafe-redirect@example.test',
                'preferred_locale' => 'en',
                'password' => 'Safe-Password-123!',
                'profile_type' => 'individual',
                'status' => 'active',
                'next' => 'https://example.com',
            ])
            ->assertSessionHasErrors('next');

        $this->assertDatabaseMissing('users', ['email' => 'unsafe-redirect@example.test']);
    }

    public function test_lease_detail_reports_real_move_in_progress_without_exposing_it_to_tenants(): void
    {
        Storage::fake('local');

        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio, [
            'name' => 'Checklist Tenant',
        ]);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio);
        $lease = $this->createLease($portfolio, $tenant, $asset, $owner, [
            'status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->get(route('leases.show', $lease))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/leases/show')
                ->where('detailPage.mode', 'admin')
                ->where('detailPage.progress.title', 'Prepare this tenancy for handover')
                ->where('detailPage.header.actions', fn ($actions) => collect($actions)->contains(
                    fn (array $action): bool => $action['label'] === 'Contract PDF'
                        && ($action['external'] ?? false) === true,
                ))
                ->where('detailPage.workflow.actions', fn ($actions) => collect($actions)->contains(
                    fn (array $action): bool => $action['label'] === 'Tenant statement'
                        && ($action['external'] ?? false) === true,
                ))
                ->where('detailPage.progress.completed', 3)
                ->where('detailPage.progress.total', 6)
                ->where('detailPage.progress.steps.0.state', 'complete')
                ->where('detailPage.progress.steps.2.download', true)
                ->where('detailPage.progress.steps.3.state', 'current')
                ->where('detailPage.progress.steps.4.state', 'pending')
                ->where('detailPage.progress.steps.5.state', 'pending'));

        $this->actingAs($tenantUser)
            ->get(route('leases.show', $lease))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.mode', 'tenant')
                ->where('detailPage.progress', null));

        Storage::disk('local')->put('documents/signed-checklist.pdf', '%PDF-1.4 signed');
        Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'signed_contract',
            'title_en' => 'Signed checklist contract',
            'title_ar' => 'عقد قائمة التحقق الموقع',
            'disk' => 'local',
            'file_path' => 'documents/signed-checklist.pdf',
            'original_name' => 'signed-checklist.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 15,
            'is_public' => true,
        ]);
        $lease->update(['status' => 'active']);
        Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'OPENING-PAYMENT',
            'type' => 'deposit',
            'method' => 'bank_transfer',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 1000,
            'currency' => 'SAR',
        ]);

        $this->actingAs($owner)
            ->get(route('leases.show', $lease))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.progress.completed', 6)
                ->where('detailPage.progress.summary', '6 of 6 ready')
                ->where('detailPage.progress.collapseWhenComplete', true)
                ->where('detailPage.progress.expandLabel', 'Show checklist')
                ->where('detailPage.progress.collapseLabel', 'Hide checklist')
                ->where('detailPage.progress.steps', fn ($steps) => collect($steps)
                    ->every(fn (array $step): bool => $step['state'] === 'complete')));
    }

    public function test_onboarding_copy_and_progress_are_available_in_arabic(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, [
            'preferred_locale' => 'ar',
        ]);
        $tenant = $this->createTenantProfile(
            $portfolio,
            $this->createUserWithRole('tenant', $portfolio),
        );
        $lease = $this->createLease(
            $portfolio,
            $tenant,
            $this->createAsset($portfolio),
            $owner,
            ['status' => 'draft'],
        );
        $vacantAsset = $this->createAsset($portfolio);

        $this->actingAs($owner)
            ->get(route('tenants.create', ['next' => 'lease', 'locale' => 'ar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.title', 'إضافة مستأجر وبدء التأجير')
                ->where('formPage.submitLabel', 'متابعة إلى العقد'));

        $this->actingAs($owner)
            ->get(route('leases.create', [
                'asset_id' => $vacantAsset->id,
                'locale' => 'ar',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('formPage.initialValues.asset_id', (string) $vacantAsset->id)
                ->where('formPage.headerActions.0.label', 'إضافة مستأجر جديد'));

        $this->actingAs($owner)
            ->get(route('leases.show', ['lease' => $lease, 'locale' => 'ar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('detailPage.progress.title', 'جهّز هذا التأجير للتسليم')
                ->where('detailPage.progress.summary', '3 من 6 جاهزة'));
    }
}
