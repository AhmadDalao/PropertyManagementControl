<?php

namespace App\Modules\Wording\Queries;

use App\Models\Asset;
use App\Models\Portfolio;
use App\Modules\Wording\Support\ContentTranslationItem;
use Illuminate\Database\Eloquent\Builder;

class PropertyContentTranslationQuery
{
    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    public function items(): array
    {
        return [...$this->portfolios(), ...$this->assets()];
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
                    ->where(fn (Builder $query) => $query
                        ->whereNull('address_ar')
                        ->orWhere('address_ar', ''))))
            ->get()
            ->map(fn (Portfolio $portfolio): array => ContentTranslationItem::make(
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
                    ->where(fn (Builder $query) => $query
                        ->whereNull('address_ar')
                        ->orWhere('address_ar', ''))))
            ->limit(500)
            ->get()
            ->map(fn (Asset $asset): array => ContentTranslationItem::make(
                'assets',
                $asset->title_en,
                $asset->code,
                blank($asset->title_ar) ? 'title_ar' : 'address_ar',
                route('assets.edit', $asset),
            ))
            ->all();
    }
}
