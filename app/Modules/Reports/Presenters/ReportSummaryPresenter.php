<?php

namespace App\Modules\Reports\Presenters;

use App\Models\MaintenanceRequest;
use App\Modules\Reports\Data\LeaseReportSnapshot;
use App\Modules\Reports\Data\PortfolioReportData;
use Illuminate\Support\Collection;

final class ReportSummaryPresenter
{
    public function __construct(
        private readonly ReportCurrencySummaryPresenter $currencies,
    ) {}

    /**
     * @param  Collection<int, MaintenanceRequest>  $maintenanceBacklog
     * @return array<string, mixed>
     */
    public function present(
        PortfolioReportData $data,
        LeaseReportSnapshot $leases,
        Collection $maintenanceBacklog,
    ): array {
        $currencyTotals = $this->currencies->present($data, $leases);
        $singleCurrency = count($currencyTotals) === 1
            ? $currencyTotals[0]
            : null;
        $rentableAssets = $data->assets->where('rentable', true);
        $occupiedAssets = $rentableAssets->whereIn(
            'occupancy_status',
            ['occupied', 'partially_occupied'],
        );

        return [
            'currency' => $singleCurrency['currency'] ?? null,
            'currencyCount' => count($currencyTotals),
            'currencyTotals' => $currencyTotals,
            'revenue' => $singleCurrency['revenue'] ?? null,
            'expenses' => $singleCurrency['expenses'] ?? null,
            'net' => $singleCurrency['net'] ?? null,
            'scheduledDue' => $singleCurrency['scheduledDue'] ?? null,
            'scheduledPaid' => $singleCurrency['scheduledPaid'] ?? null,
            'collectionRate' => $singleCurrency['collectionRate'] ?? null,
            'occupancyRate' => $rentableAssets->count() > 0
                ? round(($occupiedAssets->count() / $rentableAssets->count()) * 100, 2)
                : 0,
            'arrears' => $singleCurrency['arrears'] ?? null,
            'contractBalance' => $singleCurrency['contractBalance'] ?? null,
            'activeLeases' => $leases->activeLeases,
            'leasesInArrears' => $leases->arrearsLeases->count(),
            'openRequests' => $maintenanceBacklog->count(),
            'resolvedRequests' => $data->resolvedMaintenanceRequests->count(),
        ];
    }
}
