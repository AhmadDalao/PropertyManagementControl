<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_page_update_keeps_only_one_homepage(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $oldHome = CmsPage::query()->create([
            'slug' => 'home',
            'title_en' => 'Old Home',
            'title_ar' => 'الرئيسية القديمة',
            'status' => 'published',
            'is_homepage' => true,
            'is_visible' => true,
            'published_at' => now(),
        ]);
        $newHome = CmsPage::query()->create([
            'slug' => 'new-home',
            'title_en' => 'New Home',
            'title_ar' => 'الرئيسية الجديدة',
            'status' => 'draft',
            'is_homepage' => false,
            'is_visible' => true,
        ]);

        $this->actingAs($superadmin)
            ->put(route('cms.pages.update', $newHome), [
                'slug' => 'new-homepage',
                'title_en' => 'New Home',
                'title_ar' => 'الرئيسية الجديدة',
                'status' => 'published',
                'is_homepage' => true,
                'is_visible' => true,
            ])
            ->assertRedirect(route('cms.index'));

        $this->assertFalse($oldHome->fresh()->is_homepage);
        $this->assertTrue($newHome->fresh()->is_homepage);
        $this->assertSame('new-homepage', $newHome->fresh()->slug);
    }

    public function test_superadmin_can_archive_page_and_section(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $page = CmsPage::query()->create([
            'slug' => 'services',
            'title_en' => 'Services',
            'title_ar' => 'الخدمات',
            'status' => 'published',
            'is_homepage' => true,
            'is_visible' => true,
        ]);
        $section = CmsSection::query()->create([
            'section_type' => 'content',
            'name_en' => 'Services block',
            'name_ar' => 'قسم الخدمات',
            'content_en' => ['headline' => 'Services'],
            'content_ar' => ['headline' => 'الخدمات'],
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->delete(route('cms.pages.destroy', $page))
            ->assertRedirect(route('cms.index'));

        $this->actingAs($superadmin)
            ->delete(route('cms.sections.destroy', $section))
            ->assertRedirect(route('cms.index'));

        $this->assertSame('archived', $page->fresh()->status);
        $this->assertFalse($page->fresh()->is_homepage);
        $this->assertFalse($page->fresh()->is_visible);
        $this->assertSame('archived', $section->fresh()->status);
    }

    public function test_superadmin_can_update_navigation_item_placement_and_visibility(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $page = CmsPage::query()->create([
            'slug' => 'about',
            'title_en' => 'About',
            'title_ar' => 'عن المنصة',
            'status' => 'published',
            'is_homepage' => false,
            'is_visible' => true,
        ]);
        $item = NavigationItem::query()->create([
            'cms_page_id' => null,
            'location' => 'header',
            'title_en' => 'Old',
            'title_ar' => 'قديم',
            'url' => '/old',
            'target' => '_self',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $this->actingAs($superadmin)
            ->put(route('navigation-items.update', $item), [
                'parent_id' => null,
                'cms_page_id' => $page->id,
                'location' => 'footer',
                'title_en' => 'About',
                'title_ar' => 'عن المنصة',
                'url' => '/pages/about',
                'target' => '_self',
                'sort_order' => 9,
                'is_visible' => false,
            ])
            ->assertRedirect(route('cms.index'));

        $item->refresh();

        $this->assertSame($page->id, $item->cms_page_id);
        $this->assertSame('footer', $item->location);
        $this->assertSame(9, $item->sort_order);
        $this->assertFalse($item->is_visible);
    }
}
