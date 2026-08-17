<?php

namespace App\Modules\Expenses\Support;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceWorkOrder;
use Illuminate\Validation\ValidationException;

final class ExpenseWorkOrderGuard
{
    /** @param array<string, mixed> $data */
    public function validate(array $data, int $portfolioId, ?int $maintenanceId, ?int $assetId): ?int
    {
        $workOrderId = $this->id($data['maintenance_work_order_id'] ?? null);

        if ($workOrderId === null) {
            return null;
        }

        $workOrder = MaintenanceWorkOrder::query()
            ->with('maintenanceRequest:id,asset_id')
            ->lockForUpdate()
            ->whereKey($workOrderId)
            ->where('portfolio_id', $portfolioId)
            ->first();

        if (! $workOrder
            || $maintenanceId !== $workOrder->maintenance_request_id
            || ($assetId !== null && $assetId !== $workOrder->maintenanceRequest?->asset_id)) {
            $this->fail('app.errors.expense_work_order_mismatch');
        }

        if ($workOrder->status !== 'completed' || (float) $workOrder->final_amount <= 0) {
            $this->fail('app.errors.expense_work_order_not_final');
        }

        if (abs((float) ($data['amount'] ?? 0) - (float) $workOrder->final_amount) > 0.001) {
            $this->fail('app.errors.expense_work_order_amount_mismatch', 'amount');
        }

        $duplicate = ExpenseEntry::query()
            ->where('maintenance_request_id', $maintenanceId)
            ->where('status', '!=', 'void')
            ->get(['id', 'meta_json'])
            ->contains(fn (ExpenseEntry $expense): bool => (int) data_get(
                $expense->meta_json,
                'maintenance_work_order_id',
            ) === $workOrder->id);

        if ($duplicate) {
            $this->fail('app.errors.expense_work_order_duplicate');
        }

        return $workOrder->id;
    }

    private function id(mixed $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id ? (int) $id : null;
    }

    private function fail(string $key, string $field = 'maintenance_work_order_id'): never
    {
        throw ValidationException::withMessages([$field => trans($key)]);
    }
}
