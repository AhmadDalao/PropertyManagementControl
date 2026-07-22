<?php

namespace App\Modules\Reports\Queries;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Reports\Data\PortfolioReportData;
use App\Modules\Reports\Support\ReportAccess;
use App\Modules\Reports\Support\ReportQueryScope;

final readonly class PortfolioReportDatasetQuery
{
    public function __construct(
        private ReportAccess $access,
        private ReportQueryScope $scope,
    ) {}

    /** @param array{date_from:string,date_to:string,portfolio_id:int|null} $filters */
    public function handle(User $actor, array $filters): PortfolioReportData
    {
        $this->access->ensurePortfolioFilter($actor, $filters['portfolio_id']);

        $payments = $this->scope->withinDateRange(
            $this->scope->apply(Payment::query(), $actor, $filters['portfolio_id'])
                ->where('status', 'posted')
                ->with(['lease.leaseable', 'tenantProfile.user']),
            'received_on',
            $filters['date_from'],
            $filters['date_to'],
        )->get();
        $expenses = $this->scope->withinDateRange(
            $this->scope->apply(ExpenseEntry::query(), $actor, $filters['portfolio_id'])
                ->where('status', 'posted')
                ->with('asset'),
            'incurred_on',
            $filters['date_from'],
            $filters['date_to'],
        )->get();
        $maintenanceRequests = $this->scope->withinDateRange(
            $this->scope->apply(MaintenanceRequest::query(), $actor, $filters['portfolio_id'])
                ->with(['asset', 'tenantProfile.user', 'assignedTo']),
            'created_at',
            $filters['date_from'],
            $filters['date_to'],
        )->get();

        return new PortfolioReportData(
            payments: $payments,
            expenses: $expenses,
            assets: $this->scope->apply(Asset::query(), $actor, $filters['portfolio_id'])->get(),
            leases: $this->scope->apply(Lease::query(), $actor, $filters['portfolio_id'])
                ->with(['installments', 'tenantProfile.user', 'leaseable'])
                ->get(),
            maintenanceRequests: $maintenanceRequests,
        );
    }
}
