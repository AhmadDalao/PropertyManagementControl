<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\CmsSection;
use App\Models\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CmsModuleSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_queries_exclude_hidden_pages_and_non_public_sections(): void
    {
        $hiddenPage = $this->page(['slug' => 'hidden', 'is_visible' => false]);
        $visiblePage = $this->page(['slug' => 'visible']);
        $active = $this->section(['name_en' => 'Visible block']);
        $hidden = $this->section(['name_en' => 'Hidden block']);
        $inactive = $this->section([
            'name_en' => 'Inactive block',
            'status' => 'inactive',
        ]);

        CmsPageSection::query()->create([
            'cms_page_id' => $visiblePage->id,
            'cms_section_id' => $active->id,
            'sort_order' => 1,
            'is_visible' => true,
        ]);
        CmsPageSection::query()->create([
            'cms_page_id' => $visiblePage->id,
            'cms_section_id' => $hidden->id,
            'sort_order' => 2,
            'is_visible' => false,
        ]);
        CmsPageSection::query()->create([
            'cms_page_id' => $visiblePage->id,
            'cms_section_id' => $inactive->id,
            'sort_order' => 3,
            'is_visible' => true,
        ]);

        $this->get(route('pages.show', $hiddenPage->slug))->assertNotFound();
        $this->get(route('pages.show', $visiblePage->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/page')
                ->has('page.page_sections', 1)
                ->where('page.page_sections.0.cms_section_id', $active->id));
    }

    public function test_blank_page_slugs_are_generated_without_collisions(): void
    {
        $admin = $this->createUserWithRole('superadmin');

        foreach (['الأولى', 'الثانية'] as $arabicTitle) {
            $this->actingAs($admin)
                ->post(route('cms.pages.store'), [
                    'slug' => '',
                    'title_en' => 'Operations Page',
                    'title_ar' => $arabicTitle,
                    'status' => 'draft',
                    'is_homepage' => false,
                    'is_visible' => true,
                ])
                ->assertRedirect();
        }

        $this->assertSame(
            ['operations-page', 'operations-page-2'],
            CmsPage::query()->orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_homepage_must_be_published_and_visible(): void
    {
        $admin = $this->createUserWithRole('superadmin');
        $page = $this->page([
            'slug' => 'draft-home',
            'status' => 'draft',
            'is_homepage' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('cms.pages.update', $page), [
                'slug' => $page->slug,
                'title_en' => $page->title_en,
                'title_ar' => $page->title_ar,
                'status' => 'draft',
                'is_homepage' => true,
                'is_visible' => true,
            ])
            ->assertSessionHasErrors('is_homepage');

        $this->assertFalse($page->fresh()->is_homepage);
    }

    public function test_reorder_rejects_partial_duplicate_and_foreign_compositions(): void
    {
        $admin = $this->createUserWithRole('superadmin');
        $page = $this->page();
        $otherPage = $this->page(['slug' => 'other']);
        $first = $this->attach($page, $this->section(['name_en' => 'First']), 1);
        $second = $this->attach($page, $this->section(['name_en' => 'Second']), 2);
        $foreign = $this->attach(
            $otherPage,
            $this->section(['name_en' => 'Foreign']),
            1,
        );

        foreach ([
            [[$first->id], 'ordered_ids'],
            [[$first->id, $first->id], 'ordered_ids.0'],
            [[$first->id, $foreign->id], 'ordered_ids'],
        ] as [$orderedIds, $errorKey]) {
            $this->actingAs($admin)
                ->put(route('cms.pages.sections.reorder', $page), [
                    'ordered_ids' => $orderedIds,
                ])
                ->assertSessionHasErrors($errorKey);
        }

        $this->assertSame(1, $first->fresh()->sort_order);
        $this->assertSame(2, $second->fresh()->sort_order);
    }

    public function test_archived_sections_cannot_be_attached_and_are_hidden_everywhere(): void
    {
        $admin = $this->createUserWithRole('superadmin');
        $page = $this->page();
        $archived = $this->section([
            'name_en' => 'Archived',
            'status' => 'archived',
        ]);
        $active = $this->section(['name_en' => 'Active']);
        $pageSection = $this->attach($page, $active, 1);

        $this->actingAs($admin)
            ->post(route('cms.pages.sections.store', $page), [
                'cms_section_id' => $archived->id,
                'is_visible' => true,
            ])
            ->assertSessionHasErrors('cms_section_id');

        $this->actingAs($admin)
            ->delete(route('cms.sections.destroy', $active))
            ->assertRedirect(route('cms.index'));

        $this->assertSame('archived', $active->fresh()->status);
        $this->assertFalse($pageSection->fresh()->is_visible);
    }

    public function test_visibility_updates_preserve_section_settings_and_order(): void
    {
        $admin = $this->createUserWithRole('superadmin');
        $page = $this->page();
        $this->attach($page, $this->section(['name_en' => 'First']), 1);
        $target = CmsPageSection::query()->create([
            'cms_page_id' => $page->id,
            'cms_section_id' => $this->section(['name_en' => 'Second'])->id,
            'sort_order' => 2,
            'is_visible' => true,
            'settings_json' => ['tone' => 'warm', 'width' => 'wide'],
        ]);

        $this->actingAs($admin)
            ->put(route('cms.page-sections.update', $target), [
                'is_visible' => false,
            ])
            ->assertRedirect(route('cms.pages.show', $page));

        $target->refresh();
        $this->assertFalse($target->is_visible);
        $this->assertSame(2, $target->sort_order);
        $this->assertSame(
            ['tone' => 'warm', 'width' => 'wide'],
            $target->settings_json,
        );
    }

    public function test_navigation_rejects_cross_location_cycles_and_parent_deletion(): void
    {
        $admin = $this->createUserWithRole('superadmin');
        $parent = $this->navigation('Parent');
        $child = $this->navigation('Child', $parent->id);
        $grandchild = $this->navigation('Grandchild', $child->id);
        $footer = $this->navigation('Footer', null, 'footer');

        $this->actingAs($admin)
            ->put(
                route('navigation-items.update', $parent),
                $this->navigationPayload('Parent', $grandchild->id),
            )
            ->assertSessionHasErrors('parent_id');

        $this->actingAs($admin)
            ->put(
                route('navigation-items.update', $footer),
                $this->navigationPayload('Footer', $parent->id, 'footer'),
            )
            ->assertSessionHasErrors('parent_id');

        $this->actingAs($admin)
            ->delete(route('navigation-items.destroy', $parent))
            ->assertSessionHasErrors('navigation');

        $this->assertDatabaseHas('navigation_items', ['id' => $parent->id]);
    }

    public function test_workspace_is_focused_bounded_localized_and_superadmin_only(): void
    {
        $admin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner');

        foreach (range(1, 61) as $index) {
            $this->section(['name_en' => "Section {$index}"]);
        }

        $this->actingAs($admin)
            ->get(route('cms.index', ['view' => 'sections', 'locale' => 'ar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/cms/index')
                ->where('view', 'sections')
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where('sectionLimitReached', true)
                ->has('sections', 60));

        $this->actingAs($owner)->get(route('cms.index'))->assertForbidden();
    }

    /** @param array<string, mixed> $attributes */
    private function page(array $attributes = []): CmsPage
    {
        return CmsPage::query()->create(array_merge([
            'slug' => 'page-'.str()->random(8),
            'title_en' => 'Public page',
            'title_ar' => 'صفحة عامة',
            'status' => 'published',
            'is_homepage' => false,
            'is_visible' => true,
            'published_at' => now(),
        ], $attributes));
    }

    /** @param array<string, mixed> $attributes */
    private function section(array $attributes = []): CmsSection
    {
        return CmsSection::query()->create(array_merge([
            'section_type' => 'content',
            'name_en' => 'Content block',
            'name_ar' => 'قسم محتوى',
            'content_en' => ['headline' => 'Content'],
            'content_ar' => ['headline' => 'المحتوى'],
            'status' => 'active',
        ], $attributes));
    }

    private function attach(
        CmsPage $page,
        CmsSection $section,
        int $order,
    ): CmsPageSection {
        return CmsPageSection::query()->create([
            'cms_page_id' => $page->id,
            'cms_section_id' => $section->id,
            'sort_order' => $order,
            'is_visible' => true,
        ]);
    }

    private function navigation(
        string $title,
        ?int $parentId = null,
        string $location = 'header',
    ): NavigationItem {
        return NavigationItem::query()->create(
            $this->navigationPayload($title, $parentId, $location),
        );
    }

    /** @return array<string, mixed> */
    private function navigationPayload(
        string $title,
        ?int $parentId = null,
        string $location = 'header',
    ): array {
        return [
            'parent_id' => $parentId,
            'cms_page_id' => null,
            'location' => $location,
            'title_en' => $title,
            'title_ar' => 'رابط '.$title,
            'url' => '/'.str($title)->slug(),
            'target' => '_self',
            'sort_order' => 1,
            'is_visible' => true,
        ];
    }
}
