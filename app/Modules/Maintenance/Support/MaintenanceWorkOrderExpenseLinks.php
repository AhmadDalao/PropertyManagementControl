<?php

namespace App\Modules\Maintenance\Support;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceWorkOrder;

final class MaintenanceWorkOrderExpenseLinks
{
    public function recorded(MaintenanceWorkOrder $workOrder): ?ExpenseEntry
    {
        return ExpenseEntry::query()
            ->where('portfolio_id', $workOrder->portfolio_id)
            ->where('maintenance_request_id', $workOrder->maintenance_request_id)
            ->where('status', '!=', 'void')
            ->get(['id', 'meta_json'])
            ->first(fn (ExpenseEntry $expense): bool => (int) data_get(
                $expense->meta_json,
                'maintenance_work_order_id',
            ) === $workOrder->id);
    }

    public function create(MaintenanceWorkOrder $workOrder): string
    {
        return route('expenses.create', [
            'portfolio_id' => $workOrder->portfolio_id,
            'asset_id' => $workOrder->maintenanceRequest?->asset_id,
            'maintenance_request_id' => $workOrder->maintenance_request_id,
            'maintenance_work_order_id' => $workOrder->id,
            'vendor_name' => $workOrder->vendor_name,
            'amount' => $workOrder->final_amount,
            'title' => trans('app.work_orders.expense_title', [
                'reference' => $workOrder->reference_code,
            ]),
            'description' => $workOrder->completion_notes,
        ]);
    }
}
