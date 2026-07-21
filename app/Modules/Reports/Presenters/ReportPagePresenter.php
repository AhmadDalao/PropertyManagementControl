<?php

namespace App\Modules\Reports\Presenters;

use App\Models\User;
use App\Modules\Reports\Queries\PortfolioReportQuery;
use App\Modules\Reports\Queries\ReportPresetQuery;
use App\Modules\Shared\PortfolioScope;

class ReportPagePresenter
{
    public function __construct(
        private readonly PortfolioReportQuery $reports,
        private readonly ReportPresetQuery $presets,
        private readonly PortfolioScope $portfolios,
    ) {}

    /**
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null}  $filters
     * @return array<string, mixed>
     */
    public function present(User $actor, array $filters): array
    {
        return [
            ...$this->reports->handle($actor, $filters),
            'filters' => $filters,
            'portfolioOptions' => $this->portfolios->options($actor),
            'savedPresets' => $this->presets->visibleTo($actor),
            'presetVisibilityOptions' => $this->visibilityOptions($actor, $filters['portfolio_id']),
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
