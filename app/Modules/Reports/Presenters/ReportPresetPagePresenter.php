<?php

namespace App\Modules\Reports\Presenters;

use App\Models\ReportPreset;
use App\Models\User;
use App\Modules\Reports\Queries\ReportPresetQuery;
use App\Modules\Reports\Support\ReportAccess;
use App\Modules\Reports\Support\ReportFilterSet;
use App\Modules\Reports\Support\ReportPeriod;
use App\Modules\Reports\Support\ReportPropertyScope;
use App\Modules\Shared\PortfolioScope;

final readonly class ReportPresetPagePresenter
{
    public function __construct(
        private ReportAccess $access,
        private ReportPresetQuery $presets,
        private ReportPresetPresenter $presenter,
        private ReportFilterSet $filters,
        private ReportPeriod $periods,
        private ReportPropertyScope $properties,
        private PortfolioScope $portfolios,
    ) {}

    /** @return array<string, mixed> */
    public function index(User $actor): array
    {
        $this->access->ensureManager($actor);

        return [
            'savedPresets' => $this->presets->visibleTo($actor),
        ];
    }

    /**
     * @param  array{period:string,date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     * @return array<string, mixed>
     */
    public function create(User $actor, array $filters): array
    {
        $this->access->ensureManager($actor);

        return $this->form($actor, null, $filters);
    }

    /** @return array<string, mixed> */
    public function edit(User $actor, ReportPreset $preset): array
    {
        $this->access->ensureCanEditPreset($actor, $preset);
        $stored = $this->filters->stored($preset->filters_json);
        $period = $this->periods->normalize($stored['period'] ?? null);
        $dates = $this->periods->resolve(
            $period,
            $stored['date_from'] ?? null,
            $stored['date_to'] ?? null,
        );

        return $this->form($actor, $preset, [
            'period' => $period,
            ...$dates,
            'portfolio_id' => $stored['portfolio_id'] ?? null,
            'property_id' => $stored['property_id'] ?? null,
        ]);
    }

    /**
     * @param  array{period:string,date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     * @return array<string, mixed>
     */
    private function form(User $actor, ?ReportPreset $preset, array $filters): array
    {
        return [
            'mode' => $preset ? 'edit' : 'create',
            'preset' => $preset ? $this->presenter->present($actor, $preset) : null,
            'filters' => $filters,
            'portfolioOptions' => $this->portfolios->options($actor),
            'propertyOptions' => $this->properties->options($actor),
            'visibilityOptions' => $actor->hasRole('superadmin')
                ? ['private', 'portfolio', 'global']
                : ['private', 'portfolio'],
        ];
    }
}
