<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\NavigationItem;
use App\Modules\PublicSite\Actions\SeedLandingContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicSiteModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_the_bilingual_catalog_when_cms_is_empty(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/home')
                ->where('page.id', 0)
                ->where('page.title_en', 'Property Management Control')
                ->where('page.title_ar', 'نظام إدارة العقارات')
                ->has('page.page_sections', 8)
                ->where(
                    'page.page_sections.0.section.content_ar.headline',
                    'أدر محفظتك العقارية من مركز تحكم واحد.',
                ));
    }

    public function test_landing_seed_is_idempotent_and_matches_the_fallback_contract(): void
    {
        $first = app(SeedLandingContent::class)->handle();
        $second = app(SeedLandingContent::class)->handle();

        $this->assertSame($first['page_id'], $second['page_id']);
        $this->assertSame(8, $second['sections']);
        $this->assertSame(8, $second['navigation_items']);
        $this->assertSame(1, CmsPage::query()->where('is_homepage', true)->count());
        $this->assertSame(8, CmsSection::query()->count());
        $this->assertSame(4, NavigationItem::query()->where('location', 'header')->count());
        $this->assertSame(4, NavigationItem::query()->where('location', 'footer')->count());
        $this->assertSame(
            [1, 2, 3, 4],
            NavigationItem::query()
                ->where('location', 'footer')
                ->orderBy('sort_order')
                ->pluck('sort_order')
                ->all(),
        );

        $homepage = CmsPage::query()->where('is_homepage', true)->firstOrFail();
        $this->assertSame(8, $homepage->pageSections()->count());

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('page.id', $homepage->id)
                ->has('page.page_sections', 8)
                ->has('publicNavigation.header', 4)
                ->has('publicNavigation.footer', 4));
    }

    public function test_arabic_homepage_exposes_arabic_direction_and_content(): void
    {
        $this->get(route('home', ['locale' => 'ar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('app.locale', 'ar')
                ->where('app.direction', 'rtl')
                ->where(
                    'page.page_sections.0.section.content_ar.headline',
                    'أدر محفظتك العقارية من مركز تحكم واحد.',
                ));
    }

    public function test_public_navigation_exposes_only_visible_children_with_live_page_destinations(): void
    {
        app(SeedLandingContent::class)->handle();
        $homepage = CmsPage::query()->where('is_homepage', true)->firstOrFail();
        $parent = NavigationItem::query()->where('url', '/')->firstOrFail();
        NavigationItem::query()->create([
            'parent_id' => $parent->id,
            'cms_page_id' => $homepage->id,
            'location' => 'header',
            'title_en' => 'Visible child',
            'title_ar' => 'رابط فرعي ظاهر',
            'url' => '/stale-path',
            'target' => '_self',
            'sort_order' => 1,
            'is_visible' => true,
        ]);
        NavigationItem::query()->create([
            'parent_id' => $parent->id,
            'cms_page_id' => null,
            'location' => 'header',
            'title_en' => 'Hidden child',
            'title_ar' => 'رابط فرعي مخفي',
            'url' => '/hidden-child',
            'target' => '_self',
            'sort_order' => 2,
            'is_visible' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('publicNavigation.header.0.children', 1)
                ->where('publicNavigation.header.0.children.0.title_en', 'Visible child')
                ->where('publicNavigation.header.0.children.0.page.slug', $homepage->slug));
    }

    public function test_public_footer_navigation_excludes_hidden_and_draft_destinations(): void
    {
        $livePage = CmsPage::query()->create([
            'slug' => 'footer-live',
            'title_en' => 'Footer live',
            'title_ar' => 'تذييل منشور',
            'status' => 'published',
            'is_homepage' => false,
            'is_visible' => true,
            'published_at' => now(),
        ]);
        $draftPage = CmsPage::query()->create([
            'slug' => 'footer-draft',
            'title_en' => 'Footer draft',
            'title_ar' => 'تذييل مسودة',
            'status' => 'draft',
            'is_homepage' => false,
            'is_visible' => true,
        ]);
        foreach ([
            [$livePage, true, 'Live footer'],
            [$draftPage, true, 'Draft footer'],
            [null, false, 'Hidden footer'],
        ] as [$page, $visible, $title]) {
            NavigationItem::query()->create([
                'cms_page_id' => $page?->id,
                'location' => 'footer',
                'title_en' => $title,
                'title_ar' => 'رابط تذييل',
                'url' => '/'.str($title)->slug(),
                'target' => '_self',
                'sort_order' => 1,
                'is_visible' => $visible,
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('publicNavigation.footer', 1)
                ->where('publicNavigation.footer.0.title_en', 'Live footer')
                ->where('publicNavigation.footer.0.page.slug', 'footer-live'));
    }
}
