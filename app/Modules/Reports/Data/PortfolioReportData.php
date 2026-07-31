<?php

namespace App\Modules\Reports\Data;

use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

final readonly class PortfolioReportData
{
    /**
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, ExpenseEntry>  $expenses
     * @param  Collection<int, Asset>  $assets
     * @param  Collection<int, Lease>  $leases
     * @param  Collection<int, MaintenanceRequest>  $maintenanceRequests
     * @param  Collection<int, MaintenanceRequest>  $resolvedMaintenanceRequests
     * @param  Collection<int, Document>  $documents
     */
    public function __construct(
        public Collection $payments,
        public Collection $expenses,
        public Collection $assets,
        public Collection $leases,
        public Collection $maintenanceRequests,
        public Collection $resolvedMaintenanceRequests,
        public Collection $documents,
    ) {}
}
