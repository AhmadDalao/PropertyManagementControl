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
use App\Modules\Reports\Support\ReportPropertyScope;
use App\Modules\Reports\Support\ReportQueryScope;
use Illuminate\Database\Eloquent\Builder;

final readonly class PortfolioReportDatasetQuery
{
    public function __construct(
        private ReportAccess $access,
        private ReportQueryScope $scope,
        private ReportPropertyScope $properties,
    ) {}

    /** @param array{date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null} $filters */
    public function handle(User $actor, array $filters): PortfolioReportData
    {
        $this->access->ensurePortfolioFilter($actor, $filters['portfolio_id']);
        $assetIds = $this->properties->assetIds(
            $actor,
            $filters['portfolio_id'],
            $filters['property_id'],
        );

        $payments = $this->scope->withinDateRange(
            $this->scope->apply(Payment::query(), $actor, $filters['portfolio_id'])
                ->where('status', 'posted')
                ->when(
                    $assetIds !== null,
                    fn (Builder $query) => $query->whereHas(
                        'lease',
                        fn (Builder $leases) => $this->scopeLeases($leases, $assetIds),
                    ),
                )
                ->with(['lease.leaseable', 'tenantProfile.user']),
            'received_on',
            $filters['date_from'],
            $filters['date_to'],
        )->get();
        $expenses = $this->scope->withinDateRange(
            $this->scope->apply(ExpenseEntry::query(), $actor, $filters['portfolio_id'])
                ->where('status', 'posted')
                ->when(
                    $assetIds !== null,
                    fn (Builder $query) => $query->whereIn('asset_id', $assetIds),
                )
                ->with('asset'),
            'incurred_on',
            $filters['date_from'],
            $filters['date_to'],
        )->get();
        $maintenanceRequests = $this->scope->withinDateRange(
            $this->scope->apply(MaintenanceRequest::query(), $actor, $filters['portfolio_id'])
                ->when(
                    $assetIds !== null,
                    fn (Builder $query) => $query->whereIn('asset_id', $assetIds),
                )
                ->with(['asset', 'tenantProfile.user', 'assignedTo']),
            'created_at',
            $filters['date_from'],
            $filters['date_to'],
        )->get();
        $assets = $this->scope->apply(Asset::query(), $actor, $filters['portfolio_id']);
        $leases = $this->scope->apply(Lease::query(), $actor, $filters['portfolio_id']);

        if ($assetIds !== null) {
            $assets->whereIn('id', $assetIds);
            $this->scopeLeases($leases, $assetIds);
        }

        return new PortfolioReportData(
            payments: $payments,
            expenses: $expenses,
            assets: $assets->get(),
            leases: $leases
                ->with(['installments', 'tenantProfile.user', 'leaseable'])
                ->get(),
            maintenanceRequests: $maintenanceRequests,
        );
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, int>  $assetIds
     * @return Builder<TModel>
     */
    private function scopeLeases(Builder $query, array $assetIds): Builder
    {
        return $query
            ->whereIn('leaseable_type', $this->properties->leaseableTypes())
            ->whereIn('leaseable_id', $assetIds);
    }
}
