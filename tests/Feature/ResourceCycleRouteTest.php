<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\CmsSection;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\MediaFile;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ResourceCycleRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_operational_resources_have_create_show_and_edit_pages(): void
    {
        $portfolio = $this->createPortfolio();
        $owner = $this->createUserWithRole('owner', $portfolio);
        $manager = $this->createUserWithRole('property_manager', $portfolio);
        $tenantUser = $this->createUserWithRole('tenant', $portfolio);
        $tenant = $this->createTenantProfile($portfolio, $tenantUser);
        $asset = $this->createAsset($portfolio, ['title_en' => 'Cycle Unit']);
        $lease = $this->createLease($portfolio, $tenant, $asset, $manager);
        $payment = Payment::query()->create([
            'portfolio_id' => $portfolio->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'recorded_by_user_id' => $owner->id,
            'reference' => 'CYCLE-PAYMENT',
            'type' => 'rent',
            'method' => 'cash',
            'status' => 'posted',
            'received_on' => now()->toDateString(),
            'amount' => 500,
            'currency' => 'SAR',
        ]);
        $maintenance = MaintenanceRequest::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenant->id,
            'submitted_by_user_id' => $tenantUser->id,
            'category' => 'electricity',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Cycle service request',
            'description' => 'Breaker needs inspection.',
            'requested_at' => now(),
        ]);
        $expense = ExpenseEntry::query()->create([
            'portfolio_id' => $portfolio->id,
            'asset_id' => $asset->id,
            'lease_id' => $lease->id,
            'maintenance_request_id' => $maintenance->id,
            'created_by_user_id' => $owner->id,
            'category' => 'maintenance',
            'title' => 'Cycle expense',
            'incurred_on' => now()->toDateString(),
            'amount' => 120,
            'currency' => 'SAR',
            'status' => 'posted',
        ]);
        $document = Document::query()->create([
            'portfolio_id' => $portfolio->id,
            'uploaded_by_user_id' => $owner->id,
            'documentable_type' => $lease::class,
            'documentable_id' => $lease->id,
            'type' => 'lease_contract',
            'title_en' => 'Cycle contract',
            'disk' => 'local',
            'file_path' => 'documents/cycle.pdf',
            'original_name' => 'cycle.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
            'is_public' => true,
        ]);
        $media = MediaFile::query()->create([
            'uploaded_by_user_id' => $owner->id,
            'portfolio_id' => $portfolio->id,
            'collection' => 'units',
            'disk' => 'public',
            'path' => 'media/cycle.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 10,
            'title_en' => 'Cycle media',
            'visibility' => 'public',
        ]);

        $createRoutes = [
            'users.create',
            'assets.create',
            'tenants.create',
            'leases.create',
            'payments.create',
            'maintenance-requests.create',
            'expenses.create',
            'documents.create',
            'media-files.create',
        ];

        $expectedLayouts = [
            'users.create' => 'user',
            'assets.create' => 'asset',
            'tenants.create' => 'tenant',
            'leases.create' => 'lease',
            'payments.create' => 'payment',
            'maintenance-requests.create' => 'maintenance',
            'expenses.create' => 'expense',
            'documents.create' => 'document',
            'media-files.create' => 'media',
        ];

        foreach ($createRoutes as $routeName) {
            $component = match ($routeName) {
                'maintenance-requests.create' => 'admin/maintenance/create',
                'payments.create' => 'admin/payments/form',
                default => 'admin/resource-form',
            };

            $this->actingAs($owner)
                ->get(route($routeName))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component)
                    ->where('formPage.layout', $expectedLayouts[$routeName]));
        }

        $detailRoutes = [
            ['portfolios.show', $portfolio],
            ['users.show', $tenantUser],
            ['assets.show', $asset],
            ['tenants.show', $tenant],
            ['leases.show', $lease],
            ['payments.show', $payment],
            ['maintenance-requests.show', $maintenance],
            ['expenses.show', $expense],
            ['documents.show', $document],
        ];

        foreach ($detailRoutes as [$routeName, $model]) {
            $component = match ($routeName) {
                'portfolios.show' => 'admin/portfolios/show',
                'users.show' => 'admin/users/show',
                'assets.show' => 'admin/assets/show',
                'tenants.show' => 'admin/tenants/show',
                'leases.show' => 'admin/leases/show',
                'maintenance-requests.show' => 'admin/maintenance/show',
                'payments.show' => 'admin/payments/show',
                'expenses.show' => 'admin/expenses/show',
                'documents.show' => 'admin/documents/show',
                default => 'admin/resource-show',
            };

            $this->actingAs($owner)
                ->get(route($routeName, $model))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }

        $this->actingAs($owner)
            ->get(route('media-files.show', $media))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/media/show'));

        $editRoutes = [
            ['portfolios.edit', $portfolio],
            ['users.edit', $tenantUser],
            ['assets.edit', $asset],
            ['tenants.edit', $tenant],
            ['leases.edit', $lease],
            ['payments.edit', $payment],
            ['maintenance-requests.edit', $maintenance],
            ['expenses.edit', $expense],
            ['documents.edit', $document],
            ['media-files.edit', $media],
        ];

        $expectedEditLayouts = [
            'portfolios.edit' => 'portfolio',
            'users.edit' => 'user',
            'assets.edit' => 'asset',
            'tenants.edit' => 'tenant',
            'leases.edit' => 'lease',
            'payments.edit' => 'payment',
            'maintenance-requests.edit' => 'maintenance',
            'expenses.edit' => 'expense',
            'documents.edit' => 'document',
            'media-files.edit' => 'media',
        ];

        foreach ($editRoutes as [$routeName, $model]) {
            $component = match ($routeName) {
                'maintenance-requests.edit' => 'admin/maintenance/triage',
                'payments.edit' => 'admin/payments/form',
                default => 'admin/resource-form',
            };

            $this->actingAs($owner)
                ->get(route($routeName, $model))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component)
                    ->where('formPage.layout', $expectedEditLayouts[$routeName]));
        }
    }

    public function test_superadmin_can_open_cms_builder_and_reorder_page_sections(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $page = CmsPage::query()->create([
            'slug' => 'cycle-page',
            'title_en' => 'Cycle Page',
            'title_ar' => 'صفحة الدورة',
            'status' => 'published',
            'is_homepage' => false,
            'is_visible' => true,
        ]);
        $hero = CmsSection::query()->create([
            'section_type' => 'hero',
            'name_en' => 'Hero',
            'name_ar' => 'البطل',
            'content_en' => ['headline' => 'Hero'],
            'content_ar' => ['headline' => 'البطل'],
            'status' => 'active',
        ]);
        $faq = CmsSection::query()->create([
            'section_type' => 'faq',
            'name_en' => 'FAQ',
            'name_ar' => 'الأسئلة',
            'content_en' => ['headline' => 'FAQ'],
            'content_ar' => ['headline' => 'الأسئلة'],
            'status' => 'active',
        ]);
        $first = CmsPageSection::query()->create([
            'cms_page_id' => $page->id,
            'cms_section_id' => $hero->id,
            'sort_order' => 1,
            'is_visible' => true,
        ]);
        $second = CmsPageSection::query()->create([
            'cms_page_id' => $page->id,
            'cms_section_id' => $faq->id,
            'sort_order' => 2,
            'is_visible' => true,
        ]);

        $this->actingAs($superadmin)
            ->get(route('cms.pages.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-form')
                ->where('formPage.layout', 'cms'));

        $this->actingAs($superadmin)
            ->get(route('cms.pages.show', $page))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/cms/builder')
                ->where('sections.0.page_sections_count', 1)
                ->where('sections.1.page_sections_count', 1));

        $this->actingAs($superadmin)
            ->get(route('cms.pages.edit', $page))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/resource-form')
                ->where('formPage.layout', 'cms'));

        $this->actingAs($superadmin)
            ->put(route('cms.pages.sections.reorder', $page), [
                'ordered_ids' => [$second->id, $first->id],
            ])
            ->assertRedirect(route('cms.pages.show', $page));

        $this->assertSame(2, $first->fresh()->sort_order);
        $this->assertSame(1, $second->fresh()->sort_order);
    }
}
