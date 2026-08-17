<?php

namespace App\Modules\Maintenance\Data;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;

final readonly class MaintenanceWorkOrderDetailData
{
    public function __construct(
        public MaintenanceWorkOrder $workOrder,
        public MaintenanceRequest $request,
        public User $actor,
        public string $statusLabel,
        public string $statusTone,
        public string $scheduleCode,
        public string $scheduleLabel,
        public string $scheduleTone,
        public ?string $estimatedAmount,
        public ?string $finalAmount,
        public ?string $varianceAmount,
        public string $varianceTone,
        public bool $expensesEnabled,
        public ?string $expenseCreateUrl,
        public ?string $recordedExpenseUrl,
    ) {}
}
