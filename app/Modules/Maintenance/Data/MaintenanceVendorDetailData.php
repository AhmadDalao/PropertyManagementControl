<?php

namespace App\Modules\Maintenance\Data;

use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class MaintenanceVendorDetailData
{
    /**
     * @param  Collection<int, MaintenanceWorkOrder>  $openWorkOrders
     * @param  Collection<int, MaintenanceWorkOrder>  $historyWorkOrders
     * @param  array{total:int,open:int,active:int,draft:int,completed:int,cancelled:int,overdue:int,today:int,upcoming:int,unscheduled:int,properties:int}  $counts
     * @param  array{active_quoted:float,completed_quoted:float,completed_final:float,currency:string}  $financial
     */
    public function __construct(
        public MaintenanceVendor $vendor,
        public User $actor,
        public Collection $openWorkOrders,
        public Collection $historyWorkOrders,
        public ?MaintenanceWorkOrder $nextWorkOrder,
        public array $counts,
        public array $financial,
        public string $statusLabel,
        public string $statusTone,
        public string $categoryLabel,
    ) {}
}
