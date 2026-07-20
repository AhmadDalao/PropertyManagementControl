<?php

namespace App\Modules\Wording;

use App\Models\Asset;
use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\Document;
use App\Models\MediaFile;
use App\Models\NavigationItem;
use App\Models\Portfolio;
use App\Models\ReportPreset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TranslationCompletenessService
{
    /**
     * @return Collection<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    public function missing(?string $module = null): Collection
    {
        return collect([
            ...$this->portfolios(),
            ...$this->assets(),
            ...$this->documents(),
            ...$this->media(),
            ...$this->pages(),
            ...$this->sections(),
            ...$this->navigation(),
            ...$this->reportPresets(),
        ])
            ->when($module && $module !== 'all', fn (Collection $items) => $items->where('module', $module))
            ->sortBy(['module', 'title'])
            ->values();
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        return $this->missing()
            ->countBy('module')
            ->map(fn (int $count): int => $count)
            ->all();
    }

    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    private function portfolios(): array
    {
        return Portfolio::query()
            ->where(fn (Builder $query) => $query
                ->whereNull('name_ar')
                ->orWhere('name_ar', '')
                ->orWhere(fn (Builder $query) => $query
                    ->whereNotNull('address')
                    ->where('address', '!=', '')
                    ->where(fn (Builder $query) => $query->whereNull('address_ar')->orWhere('address_ar', ''))))
            ->get()
            ->map(fn (Portfolio $portfolio): array => $this->item(
                'portfolios',
                $portfolio->name_en,
                $portfolio->code,
                blank($portfolio->name_ar) ? 'name_ar' : 'address_ar',
                route('portfolios.edit', $portfolio),
            ))
            ->all();
    }

    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    private function assets(): array
    {
        return Asset::query()
            ->where(fn (Builder $query) => $query
                ->whereNull('title_ar')
                ->orWhere('title_ar', '')
                ->orWhere(fn (Builder $query) => $query
                    ->whereNotNull('address')
                    ->where('address', '!=', '')
                    ->where(fn (Builder $query) => $query->whereNull('address_ar')->orWhere('address_ar', ''))))
            ->limit(500)
            ->get()
            ->map(fn (Asset $asset): array => $this->item(
                'assets',
                $asset->title_en,
                $asset->code,
                blank($asset->title_ar) ? 'title_ar' : 'address_ar',
                route('assets.edit', $asset),
            ))
            ->all();
    }

    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    private function documents(): array
    {
        return Document::query()
            ->where(fn (Builder $query) => $query->whereNull('title_ar')->orWhere('title_ar', ''))
            ->limit(300)
            ->get()
            ->map(fn (Document $document): array => $this->item(
                'documents',
                $document->title_en,
                $document->original_name,
                'title_ar',
                route('documents.edit', $document),
            ))
            ->all();
    }

    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    private function media(): array
    {
        return MediaFile::query()
            ->where('visibility', 'public')
            ->where(fn (Builder $query) => $query
                ->whereNull('title_ar')
                ->orWhere('title_ar', '')
                ->orWhereNull('alt_text_ar')
                ->orWhere('alt_text_ar', ''))
            ->limit(300)
            ->get()
            ->map(fn (MediaFile $media): array => $this->item(
                'media',
                $media->title_en ?: basename($media->path),
                $media->collection,
                blank($media->title_ar) ? 'title_ar' : 'alt_text_ar',
                route('media-files.edit', $media),
            ))
            ->all();
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
                    ->where(fn (Builder $query) => $query->whereNull('excerpt_ar')->orWhere('excerpt_ar', ''))))
            ->get()
            ->map(fn (CmsPage $page): array => $this->item(
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
                    ->where(fn (Builder $query) => $query->whereNull('content_ar')->orWhere('content_ar', '[]'))))
            ->get()
            ->map(fn (CmsSection $section): array => $this->item(
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
            ->where(fn (Builder $query) => $query->whereNull('title_ar')->orWhere('title_ar', ''))
            ->get()
            ->map(fn (NavigationItem $item): array => $this->item(
                'navigation',
                $item->title_en,
                $item->location,
                'title_ar',
                route('cms.navigation.edit', $item),
            ))
            ->all();
    }

    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    private function reportPresets(): array
    {
        return ReportPreset::query()
            ->where(fn (Builder $query) => $query->whereNull('title_ar')->orWhere('title_ar', ''))
            ->get()
            ->map(fn (ReportPreset $preset): array => $this->item(
                'report_presets',
                $preset->title_en,
                $preset->resource,
                'title_ar',
                route('reports.index', ['preset' => $preset->id]),
            ))
            ->all();
    }

    /**
     * @return array{module:string,title:string,subtitle:string,missing:string,href:string}
     */
    private function item(string $module, string $title, string $subtitle, string $missing, string $href): array
    {
        return compact('module', 'title', 'subtitle', 'missing', 'href');
    }
}
