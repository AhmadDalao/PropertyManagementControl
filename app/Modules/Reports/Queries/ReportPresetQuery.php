<?php

namespace App\Modules\Reports\Queries;

use App\Models\ReportPreset;
use App\Models\User;
use App\Modules\Reports\Presenters\ReportPresetPresenter;
use App\Modules\Reports\Support\ReportAccess;
use App\Modules\Reports\Support\ReportFilterSet;
use App\Modules\Reports\Support\ReportPropertyScope;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReportPresetQuery
{
    public function __construct(
        private readonly ReportAccess $access,
        private readonly ReportFilterSet $filters,
        private readonly ReportPropertyScope $properties,
        private readonly ReportPresetPresenter $presenter,
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
            ->filter(fn (ReportPreset $preset): bool => $this->accessible($actor, $preset))
            ->map(fn (ReportPreset $preset): array => $this->presenter->present($actor, $preset))
            ->all();
    }

    /** @return array{period?:string,date_from?:string,date_to?:string,portfolio_id?:int,property_id?:int} */
    public function defaultFiltersFor(User $actor): array
    {
        $this->access->ensureManager($actor);
        $preset = ReportPreset::query()
            ->where('user_id', $actor->id)
            ->where('resource', 'portfolio-report')
            ->where('is_default', true)
            ->latest()
            ->first();

        return $preset instanceof ReportPreset && $this->accessible($actor, $preset)
            ? $this->filters->stored($preset->filters_json)
            : [];
    }

    private function accessible(User $actor, ReportPreset $preset): bool
    {
        $filters = $this->filters->stored($preset->filters_json);

        try {
            $this->properties->assetIds(
                $actor,
                $filters['portfolio_id'] ?? null,
                $filters['property_id'] ?? null,
            );

            return true;
        } catch (HttpException) {
            return false;
        }
    }
}
