<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Modules\Maintenance\Data\MaintenanceWorkOrderDetailData;
use App\Modules\Maintenance\Support\MaintenanceWorkOrderAccess;
use App\Modules\Maintenance\Support\MaintenanceWorkOrderExpenseLinks;
use App\Modules\Portfolios\Support\PortfolioModules;

final class MaintenanceWorkOrderDetailQuery
{
    public function __construct(
        private readonly MaintenanceWorkOrderAccess $access,
        private readonly MaintenanceWorkOrderExpenseLinks $expenseLinks,
    ) {}

    public function get(MaintenanceWorkOrder $workOrder, User $actor): MaintenanceWorkOrderDetailData
    {
        $this->access->ensureCanManage($actor, $workOrder);
        $workOrder->loadMissing([
            'portfolio',
            'maintenanceRequest.asset',
            'maintenanceRequest.tenantProfile.user',
            'vendor',
            'assignedTo',
            'createdBy',
        ]);
        $request = $workOrder->maintenanceRequest;

        if ($request === null) {
            abort(404);
        }

        $scheduleCode = $this->scheduleCode($workOrder);
        $expensesEnabled = PortfolioModules::enabledForUser($actor, 'expenses');
        $recordedExpense = $this->expenseLinks->recorded($workOrder);
        $canRecordExpense = $expensesEnabled
            && $recordedExpense === null
            && $workOrder->status === 'completed'
            && (float) $workOrder->final_amount > 0
            && $workOrder->portfolio?->status === 'active';
        $variance = $workOrder->estimated_amount !== null && $workOrder->final_amount !== null
            ? (float) $workOrder->final_amount - (float) $workOrder->estimated_amount
            : null;

        return new MaintenanceWorkOrderDetailData(
            workOrder: $workOrder,
            request: $request,
            actor: $actor,
            statusLabel: trans("app.status.{$workOrder->status}"),
            statusTone: $this->statusTone($workOrder->status),
            scheduleCode: $scheduleCode,
            scheduleLabel: trans("app.work_orders.schedule_state_{$scheduleCode}"),
            scheduleTone: $this->scheduleTone($scheduleCode),
            estimatedAmount: $this->money($workOrder->estimated_amount, $workOrder->currency),
            finalAmount: $this->money($workOrder->final_amount, $workOrder->currency),
            varianceAmount: $this->variance($variance, $workOrder->currency),
            varianceTone: $variance === null || $variance === 0.0
                ? 'muted'
                : ($variance > 0 ? 'danger' : 'teal'),
            expensesEnabled: $expensesEnabled,
            expenseCreateUrl: $canRecordExpense ? $this->expenseLinks->create($workOrder) : null,
            recordedExpenseUrl: $expensesEnabled && $recordedExpense
                ? route('expenses.show', $recordedExpense)
                : null,
        );
    }

    private function scheduleCode(MaintenanceWorkOrder $workOrder): string
    {
        if (in_array($workOrder->status, ['completed', 'cancelled'], true)) {
            return $workOrder->status;
        }

        if ($workOrder->scheduled_at === null) {
            return 'unscheduled';
        }

        if ($workOrder->scheduled_at->isPast()) {
            return 'overdue';
        }

        return $workOrder->scheduled_at->isToday() ? 'today' : 'upcoming';
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            'completed' => 'teal',
            'cancelled' => 'danger',
            'scheduled', 'in_progress' => 'primary',
            default => 'muted',
        };
    }

    private function scheduleTone(string $state): string
    {
        return match ($state) {
            'completed' => 'teal',
            'overdue', 'cancelled' => 'danger',
            'today', 'upcoming' => 'primary',
            default => 'muted',
        };
    }

    private function money(mixed $amount, string $currency): ?string
    {
        return $amount === null ? null : number_format((float) $amount, 2).' '.$currency;
    }

    private function variance(?float $variance, string $currency): ?string
    {
        if ($variance === null) {
            return null;
        }

        $prefix = $variance > 0 ? '+' : '';

        return $prefix.number_format($variance, 2).' '.$currency;
    }
}
