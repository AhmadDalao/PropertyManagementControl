<?php

namespace App\Modules\Reports\Presenters;

use App\Models\MaintenanceRequest;
use App\Modules\Reports\Data\LeaseReportSnapshot;
use App\Modules\Reports\Data\PortfolioReportData;
use Illuminate\Support\Collection;

final class ReportSummaryPresenter
{
    /**
     * @param  Collection<int, MaintenanceRequest>  $maintenanceBacklog
     * @return array<string, float|int>
     */
    public function present(
        PortfolioReportData $data,
        LeaseReportSnapshot $leases,
        Collection $maintenanceBacklog,
    ): array {
        $revenue = (float) $data->payments->sum('amount');
        $expenses = (float) $data->expenses->sum('amount');
        $rentableAssets = $data->assets->where('rentable', true);
        $occupiedAssets = $rentableAssets->whereIn(
            'occupancy_status',
            ['occupied', 'partially_occupied'],
        );

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net' => $revenue - $expenses,
            'scheduledDue' => $leases->scheduledDue,
            'scheduledPaid' => $leases->scheduledPaid,
            'collectionRate' => $leases->scheduledDue > 0
                ? round(min(100, ($leases->scheduledPaid / $leases->scheduledDue) * 100), 2)
                : 0,
            'occupancyRate' => $rentableAssets->count() > 0
                ? round(($occupiedAssets->count() / $rentableAssets->count()) * 100, 2)
                : 0,
            'arrears' => $leases->arrears,
            'contractBalance' => $leases->contractBalance,
            'activeLeases' => $leases->activeLeases,
            'leasesInArrears' => $leases->arrearsLeases->count(),
            'openRequests' => $maintenanceBacklog->count(),
            'resolvedRequests' => $data->maintenanceRequests->where('status', 'resolved')->count(),
        ];
    }
}
