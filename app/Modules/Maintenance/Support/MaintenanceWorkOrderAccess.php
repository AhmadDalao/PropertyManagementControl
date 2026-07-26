<?php

namespace App\Modules\Maintenance\Support;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;

final class MaintenanceWorkOrderAccess
{
    public function __construct(private readonly MaintenanceAccess $maintenance) {}

    public function ensureCanManageRequest(User $actor, MaintenanceRequest $request): void
    {
        $this->maintenance->ensureManager($actor);
        $this->maintenance->ensureCanAccess($actor, $request);
    }

    public function ensureCanManage(User $actor, MaintenanceWorkOrder $workOrder): void
    {
        $workOrder->loadMissing('maintenanceRequest');
        abort_unless(
            $workOrder->maintenanceRequest !== null
                && $workOrder->portfolio_id === $workOrder->maintenanceRequest->portfolio_id,
            404,
        );
        $this->ensureCanManageRequest($actor, $workOrder->maintenanceRequest);
    }
}
