<?php

namespace App\Modules\Wording\Queries;

use App\Models\ReportPreset;
use App\Modules\Wording\Support\ContentTranslationItem;
use Illuminate\Database\Eloquent\Builder;

class ReportPresetContentTranslationQuery
{
    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    public function items(): array
    {
        return ReportPreset::query()
            ->where(fn (Builder $query) => $query
                ->whereNull('title_ar')
                ->orWhere('title_ar', ''))
            ->get()
            ->map(fn (ReportPreset $preset): array => ContentTranslationItem::make(
                'report_presets',
                $preset->title_en,
                $preset->resource,
                'title_ar',
                route('reports.index', ['preset' => $preset->id]),
            ))
            ->all();
    }
}
