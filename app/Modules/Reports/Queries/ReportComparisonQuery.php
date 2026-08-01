<?php

namespace App\Modules\Reports\Queries;

use App\Models\User;
use App\Modules\Reports\Data\LeaseReportSnapshot;
use App\Modules\Reports\Data\PortfolioReportData;
use App\Modules\Reports\Presenters\ReportComparisonPresenter;
use App\Modules\Reports\Support\LeaseReportSnapshotFactory;
use App\Modules\Reports\Support\ReportComparisonPeriod;

final readonly class ReportComparisonQuery
{
    public function __construct(
        private ReportComparisonPeriod $periods,
        private PortfolioReportDatasetQuery $dataset,
        private LeaseReportSnapshotFactory $leases,
        private ReportComparisonPresenter $presenter,
    ) {}

    /**
     * @param  array{period?:string,date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     * @return array<string, mixed>
     */
    public function handle(
        User $actor,
        array $filters,
        PortfolioReportData $current,
        LeaseReportSnapshot $currentLeases,
    ): array {
        $period = $this->periods->previous($filters);
        $previousFilters = [
            ...$filters,
            'period' => 'custom',
            ...$period,
        ];
        $previous = $this->dataset->handle($actor, $previousFilters);

        return $this->presenter->present(
            $current,
            $currentLeases,
            $previous,
            $this->leases->make($previous, $previousFilters),
            $period,
        );
    }
}
