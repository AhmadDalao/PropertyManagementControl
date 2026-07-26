<?php

namespace App\Modules\Maintenance\Data;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class MaintenanceDetailData
{
    /**
     * @param  Collection<int, MaintenanceUpdate>  $updates
     * @param  Collection<int, ExpenseEntry>  $expenses
     * @param  Collection<int, MaintenanceAttachment>  $attachments
     * @param  Collection<int, MaintenanceWorkOrder>  $workOrders
     */
    public function __construct(
        public MaintenanceRequest $request,
        public User $actor,
        public bool $tenantMode,
        public Collection $updates,
        public Collection $expenses,
        public Collection $attachments,
        public Collection $workOrders,
        public float $postedExpenseTotal,
    ) {}
}
