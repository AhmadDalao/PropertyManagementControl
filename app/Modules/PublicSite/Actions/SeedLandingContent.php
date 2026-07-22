<?php

namespace App\Modules\PublicSite\Actions;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\NavigationItem;
use App\Modules\PublicSite\Support\LandingContentCatalog;
use Illuminate\Support\Facades\DB;

class SeedLandingContent
{
    public function __construct(
        private readonly LandingContentCatalog $catalog,
    ) {}

    /** @return array{page_id: int, sections: int, navigation_items: int} */
    public function handle(): array
    {
        return DB::transaction(function (): array {
            $page = $this->upsertHomepage();
            $sections = $this->catalog->sections();
            $navigation = $this->catalog->navigation();

            $this->upsertSections($page, $sections);
            $this->upsertNavigation($page, $navigation);

            return [
                'page_id' => $page->id,
                'sections' => count($sections),
                'navigation_items' => count($navigation),
            ];
        });
    }

    private function upsertHomepage(): CmsPage
    {
        $definition = $this->catalog->page();
        $slug = (string) $definition['slug'];
        unset($definition['slug']);
        $definition['published_at'] = now();

        $page = CmsPage::query()->updateOrCreate(['slug' => $slug], $definition);

        CmsPage::query()
            ->whereKeyNot($page->id)
            ->where('is_homepage', true)
            ->update(['is_homepage' => false]);

        return $page;
    }

    /** @param array<int, array<string, mixed>> $definitions */
    private function upsertSections(CmsPage $page, array $definitions): void
    {
        foreach ($definitions as $index => $definition) {
            $section = CmsSection::query()->updateOrCreate(
                [
                    'section_type' => $definition['section_type'],
                    'name_en' => $definition['name_en'],
                ],
                [
                    'name_ar' => $definition['name_ar'],
                    'content_en' => $definition['content_en'],
                    'content_ar' => $definition['content_ar'],
                    'settings_json' => ['seed_key' => $definition['seed_key']],
                    'status' => 'active',
                ],
            );

            $page->pageSections()->updateOrCreate(
                ['cms_section_id' => $section->id],
                [
                    'sort_order' => $index + 1,
                    'is_visible' => true,
                    'settings_json' => ['seed_key' => $definition['seed_key']],
                ],
            );
        }
    }

    /** @param array<int, array<string, mixed>> $definitions */
    private function upsertNavigation(CmsPage $page, array $definitions): void
    {
        foreach ($definitions as $index => $definition) {
            NavigationItem::query()->updateOrCreate(
                [
                    'parent_id' => null,
                    'location' => 'header',
                    'url' => $definition['url'],
                ],
                [
                    'cms_page_id' => ($definition['links_homepage'] ?? false) ? $page->id : null,
                    'title_en' => $definition['title_en'],
                    'title_ar' => $definition['title_ar'],
                    'target' => '_self',
                    'sort_order' => $index + 1,
                    'is_visible' => true,
                ],
            );
        }
    }
}
