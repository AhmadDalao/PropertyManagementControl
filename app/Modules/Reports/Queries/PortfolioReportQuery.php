<?php

namespace App\Modules\Reports\Queries;

use App\Models\User;
use App\Modules\Reports\Presenters\ReportChartsPresenter;
use App\Modules\Reports\Presenters\ReportExpenseRowsPresenter;
use App\Modules\Reports\Presenters\ReportLeaseRowsPresenter;
use App\Modules\Reports\Presenters\ReportMaintenanceRowsPresenter;
use App\Modules\Reports\Presenters\ReportPaymentRowsPresenter;
use App\Modules\Reports\Presenters\ReportSummaryPresenter;
use App\Modules\Reports\Support\LeaseReportSnapshotFactory;

final readonly class PortfolioReportQuery
{
    public function __construct(
        private PortfolioReportDatasetQuery $dataset,
        private LeaseReportSnapshotFactory $leaseSnapshot,
        private ReportSummaryPresenter $summary,
        private ReportChartsPresenter $charts,
        private ReportLeaseRowsPresenter $leaseRows,
        private ReportPaymentRowsPresenter $paymentRows,
        private ReportExpenseRowsPresenter $expenseRows,
        private ReportMaintenanceRowsPresenter $maintenanceRows,
    ) {}

    /**
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null}  $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, array $filters): array
    {
        $data = $this->dataset->handle($actor, $filters);
        $leaseSnapshot = $this->leaseSnapshot->make($data, $filters);
        $maintenanceBacklog = $data->maintenanceRequests
            ->whereIn('status', ['open', 'in_progress'])
            ->sortByDesc('created_at')
            ->values();

        return [
            'mode' => $actor->hasRole('superadmin') ? 'superadmin' : 'portfolio',
            'summary' => $this->summary->present($data, $leaseSnapshot, $maintenanceBacklog),
            'charts' => $this->charts->present($data),
            'arrearsLeases' => $this->leaseRows->present($leaseSnapshot),
            ...$this->paymentRows->present($data),
            'recentExpenses' => $this->expenseRows->present($data),
            'maintenanceBacklog' => $this->maintenanceRows->present($maintenanceBacklog),
        ];
    }
}
