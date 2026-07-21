<?php

namespace App\Modules\Reports\Queries;

use App\Models\ReportPreset;
use App\Models\User;
use App\Modules\Reports\Support\ReportAccess;
use App\Modules\Reports\Support\ReportFilterSet;
use Illuminate\Database\Eloquent\Builder;

class ReportPresetQuery
{
    public function __construct(
        private readonly ReportAccess $access,
        private readonly ReportFilterSet $filters,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function visibleTo(User $actor): array
    {
        $this->access->ensureManager($actor);

        return ReportPreset::query()
            ->where('resource', 'portfolio-report')
            ->where(function (Builder $query) use ($actor): void {
                $query
                    ->where('user_id', $actor->id)
                    ->orWhere(function (Builder $globalQuery): void {
                        $globalQuery
                            ->where('visibility', 'global')
                            ->whereNull('portfolio_id');
                    });

                if ($actor->portfolio_id) {
                    $query->orWhere(function (Builder $portfolioQuery) use ($actor): void {
                        $portfolioQuery
                            ->where('portfolio_id', $actor->portfolio_id)
                            ->where('visibility', 'portfolio');
                    });
                }
            })
            ->latest()
            ->get()
            ->map(function (ReportPreset $preset) use ($actor): array {
                $filters = $this->filters->stored($preset->filters_json);

                return [
                    'id' => $preset->id,
                    'title_en' => $preset->title_en,
                    'title_ar' => $preset->title_ar,
                    'visibility' => $preset->visibility,
                    'is_default' => $preset->is_default,
                    'can_delete' => $this->access->canDeletePreset($actor, $preset),
                    'filters' => $filters,
                    'url' => route('reports.index', $filters),
                ];
            })
            ->all();
    }

    /** @return array{date_from?:string,date_to?:string,portfolio_id?:int} */
    public function defaultFiltersFor(User $actor): array
    {
        $this->access->ensureManager($actor);
        $preset = ReportPreset::query()
            ->where('user_id', $actor->id)
            ->where('resource', 'portfolio-report')
            ->where('is_default', true)
            ->latest()
            ->first();

        return $preset instanceof ReportPreset
            ? $this->filters->stored($preset->filters_json)
            : [];
    }
}
