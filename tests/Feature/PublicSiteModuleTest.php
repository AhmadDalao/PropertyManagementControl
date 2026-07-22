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
        $this->assertSame(4, $second['navigation_items']);
        $this->assertSame(1, CmsPage::query()->where('is_homepage', true)->count());
        $this->assertSame(8, CmsSection::query()->count());
        $this->assertSame(4, NavigationItem::query()->where('location', 'header')->count());

        $homepage = CmsPage::query()->where('is_homepage', true)->firstOrFail();
        $this->assertSame(8, $homepage->pageSections()->count());

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('page.id', $homepage->id)
                ->has('page.page_sections', 8)
                ->has('publicNavigation.header', 4));
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
}
