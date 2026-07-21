<?php

namespace App\Modules\Wording\Queries;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\NavigationItem;
use App\Modules\Wording\Support\ContentTranslationItem;
use Illuminate\Database\Eloquent\Builder;

class CmsContentTranslationQuery
{
    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    public function items(): array
    {
        return [...$this->pages(), ...$this->sections(), ...$this->navigation()];
    }

    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    private function pages(): array
    {
        return CmsPage::query()
            ->where(fn (Builder $query) => $query
                ->whereNull('title_ar')
                ->orWhere('title_ar', '')
                ->orWhere(fn (Builder $query) => $query
                    ->whereNotNull('excerpt_en')
                    ->where('excerpt_en', '!=', '')
                    ->where(fn (Builder $query) => $query
                        ->whereNull('excerpt_ar')
                        ->orWhere('excerpt_ar', ''))))
            ->get()
            ->map(fn (CmsPage $page): array => ContentTranslationItem::make(
                'cms_pages',
                $page->title_en,
                "/{$page->slug}",
                blank($page->title_ar) ? 'title_ar' : 'excerpt_ar',
                route('cms.pages.edit', $page),
            ))
            ->all();
    }

    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    private function sections(): array
    {
        return CmsSection::query()
            ->where(fn (Builder $query) => $query
                ->whereNull('name_ar')
                ->orWhere('name_ar', '')
                ->orWhere(fn (Builder $query) => $query
                    ->whereNotNull('content_en')
                    ->where(fn (Builder $query) => $query
                        ->whereNull('content_ar')
                        ->orWhere('content_ar', '[]'))))
            ->get()
            ->map(fn (CmsSection $section): array => ContentTranslationItem::make(
                'cms_sections',
                $section->name_en,
                $section->section_type,
                blank($section->name_ar) ? 'name_ar' : 'content_ar',
                route('cms.sections.edit', $section),
            ))
            ->all();
    }

    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    private function navigation(): array
    {
        return NavigationItem::query()
            ->where(fn (Builder $query) => $query
                ->whereNull('title_ar')
                ->orWhere('title_ar', ''))
            ->get()
            ->map(fn (NavigationItem $item): array => ContentTranslationItem::make(
                'navigation',
                $item->title_en,
                $item->location,
                'title_ar',
                route('cms.navigation.edit', $item),
            ))
            ->all();
    }
}
