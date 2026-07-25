<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentationWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_documentation_hides_disabled_modules_and_superadmin_pages(): void
    {
        $portfolio = $this->createPortfolio([
            'module_settings' => [
                'assets' => true,
                'users' => false,
                'payments' => false,
                'reports' => true,
            ],
        ]);
        $owner = $this->createUserWithRole('owner', $portfolio);

        $this->actingAs($owner)
            ->get(route('documentation.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/documentation/index')
                ->where('audience', 'owner')
                ->where('roleGuide.role', 'owner')
                ->where('roleGuide.routes', fn ($routes) => ! collect($routes)->contains('/payments'))
                ->missing('roleGuides')
                ->where('pageShortcuts', fn ($shortcuts) => collect($shortcuts)->contains('route', '/assets')
                    && collect($shortcuts)->contains('route', '/assets/building-setup')
                    && ! collect($shortcuts)->contains('route', '/users')
                    && ! collect($shortcuts)->contains('route', '/payments')
                    && ! collect($shortcuts)->contains('route', '/cms'))
                ->where('quickStarts', fn ($quickStarts) => collect($quickStarts)->contains('route', '/assets/building-setup')
                    && ! collect($quickStarts)->contains('route', '/payments'))
                ->where('guides', fn ($guides) => ! collect($guides)->contains('route', '/payments'))
                ->where('workflowTracks', fn ($workflows) => collect($workflows)->contains('key', 'asset_to_lease')
                    && ! collect($workflows)->contains('key', 'rent_collection')
                    && ! collect($workflows)->contains('key', 'website_publish')
                    && ! collect(collect($workflows)->firstWhere('key', 'portfolio_launch')['steps'])->contains('route', '/users'))
                ->where('moduleStatus', fn ($modules) => collect($modules)->firstWhere('key', 'payments')['enabled'] === false)
                ->missing('guides.0.roles')
                ->missing('workflowTracks.0.steps.0.module')
            );

        $this->actingAs($owner)
            ->get(route('documentation.show', 'payments-and-receipts'))
            ->assertNotFound();
    }

    public function test_manager_documentation_keeps_scoped_asset_workflow_without_owner_builder(): void
    {
        $portfolio = $this->createPortfolio();
        $manager = $this->createUserWithRole('property_manager', $portfolio);

        $this->actingAs($manager)
            ->get(route('documentation.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('audience', 'property_manager')
                ->where('pageShortcuts', fn ($shortcuts) => collect($shortcuts)->contains('route', '/assets')
                    && ! collect($shortcuts)->contains('route', '/assets/building-setup'))
                ->where('quickStarts', fn ($quickStarts) => ! collect($quickStarts)->contains('route', '/assets/building-setup'))
                ->where('workflowTracks', fn ($workflows) => collect($workflows)->contains('key', 'asset_to_lease'))
            );
    }

    public function test_tenant_documentation_only_exposes_tenant_safe_workflows(): void
    {
        $portfolio = $this->createPortfolio();
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        $this->actingAs($tenant)
            ->get(route('documentation.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/documentation/index')
                ->where('audience', 'tenant')
                ->where('roleGuide.role', 'tenant')
                ->where('quickStarts', fn ($quickStarts) => collect($quickStarts)->contains('title', 'Use the tenant portal')
                    && ! collect($quickStarts)->contains('title', 'Set up a portfolio'))
                ->where('pageShortcuts', fn ($shortcuts) => collect($shortcuts)->contains('route', '/maintenance-requests')
                    && ! collect($shortcuts)->contains('route', '/assets')
                    && ! collect($shortcuts)->contains('route', '/payments'))
                ->where('guides', fn ($guides) => collect($guides)->contains('title', 'Maintenance Requests')
                    && ! collect($guides)->contains('title', 'Website Control'))
                ->where('workflowTracks', fn ($workflows) => collect($workflows)->contains('key', 'service_request')
                    && ! collect($workflows)->contains('key', 'portfolio_launch'))
            );
    }

    public function test_role_scoped_guide_pages_render_individually(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $tenant = $this->createUserWithRole('tenant', $portfolio);

        $this->actingAs($owner)
            ->get(route('documentation.show', 'asset-control'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/documentation/show')
                ->where('guide.slug', 'asset-control')
                ->where('audience', 'owner')
                ->has('relatedGuides'));

        $this->actingAs($tenant)
            ->get(route('documentation.show', 'website-control'))
            ->assertNotFound();
    }

    public function test_arabic_documentation_contains_translated_workflows_and_guides(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio, [
            'preferred_locale' => 'ar',
        ]);

        $this->actingAs($owner)
            ->get(route('documentation.index', ['locale' => 'ar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.direction', 'rtl')
                ->where('app.translations.nav.dashboard', 'لوحة التحكم')
                ->where('workflowTracks.0.title', 'إطلاق محفظة عقارية مُدارة')
                ->where('workflowTracks.1.title', 'إدارة قائمة إجراءات العقارات اليومية')
                ->where('quickStarts', fn ($quickStarts) => collect($quickStarts)
                    ->contains('title', 'ضم مستأجر جديد')
                    && collect($quickStarts)->contains('route', '/assets/building-setup'))
                ->where(
                    'workflowTracks.2.steps.2.label',
                    'اختيار مستأجر حالي أو إضافة مستأجر جديد',
                )
                ->where('pageShortcuts', fn ($shortcuts) => collect($shortcuts)->contains(
                    fn ($shortcut) => $shortcut['route'] === '/assets/building-setup'
                        && $shortcut['label'] === 'إعداد المبنى',
                ))
                ->where('guides.0.title', 'إدارة الأصول'));
    }
}
