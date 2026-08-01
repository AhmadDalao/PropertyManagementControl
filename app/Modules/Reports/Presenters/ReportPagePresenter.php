<?php

namespace App\Modules\Reports\Presenters;

use App\Models\User;
use App\Modules\Reports\Queries\PortfolioReportQuery;
use App\Modules\Reports\Queries\ReportPresetQuery;
use App\Modules\Reports\Support\ReportPropertyScope;
use App\Modules\Shared\PortfolioScope;

class ReportPagePresenter
{
    public function __construct(
        private readonly PortfolioReportQuery $reports,
        private readonly ReportPresetQuery $presets,
        private readonly ReportLibraryPresenter $library,
        private readonly PortfolioScope $portfolios,
        private readonly ReportPropertyScope $properties,
    ) {}

    /**
     * @param  array{period:string,date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     * @return array<string, mixed>
     */
    public function present(User $actor, array $filters): array
    {
        return [
            ...$this->reports->handle($actor, $filters),
            'filters' => $filters,
            'portfolioOptions' => $this->portfolios->options($actor),
            'propertyOptions' => $this->properties->options($actor),
            'savedPresets' => $this->presets->visibleTo($actor),
            'presetVisibilityOptions' => $this->visibilityOptions($actor, $filters['portfolio_id']),
            'reportLibrary' => $this->library->present($actor, $filters),
        ];
    }

    /** @return array<int, string> */
    private function visibilityOptions(User $actor, ?int $portfolioId): array
    {
        if ($actor->hasRole('superadmin')) {
            return $portfolioId === null
                ? ['private', 'global']
                : ['private', 'portfolio', 'global'];
        }

        return ['private', 'portfolio'];
    }
}
