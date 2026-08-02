<?php

namespace App\Modules\Reports\Presenters;

use App\Models\User;
use App\Modules\Reports\Queries\PortfolioReportQuery;
use App\Modules\Reports\Support\ReportPropertyScope;
use App\Modules\Shared\PortfolioScope;

class ReportPagePresenter
{
    public function __construct(
        private readonly PortfolioReportQuery $reports,
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
        $portfolioOptions = $this->portfolios->options($actor);
        $propertyOptions = $this->properties->options($actor);

        return [
            ...$this->reports->handle($actor, $filters),
            'filters' => $filters,
            'portfolioOptions' => $portfolioOptions,
            'propertyOptions' => $propertyOptions,
            'reportLibrary' => $this->library->present(
                $actor,
                $filters,
                $portfolioOptions,
                $propertyOptions,
            ),
        ];
    }
}
