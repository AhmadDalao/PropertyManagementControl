<?php

namespace App\Modules\Expenses\Presenters;

use App\Models\ExpenseEntry;

final class ExpenseTableRowPresenter
{
    /** @return array<string, mixed> */
    public function present(ExpenseEntry $expense): array
    {
        return [
            'id' => $expense->id,
            'portfolio_id' => $expense->portfolio_id,
            'asset_id' => $expense->asset_id,
            'maintenance_request_id' => $expense->maintenance_request_id,
            'title' => $expense->title,
            'category' => $expense->category,
            'status' => $expense->status,
            'vendor_name' => $expense->vendor_name,
            'amount' => (float) $expense->amount,
            'currency' => $expense->currency,
            'incurred_on' => $expense->incurred_on?->toDateString(),
            'asset' => $expense->asset ? [
                'id' => $expense->asset->id,
                'title_en' => $expense->asset->title_en,
                'title_ar' => $expense->asset->title_ar,
                'code' => $expense->asset->code,
            ] : null,
            'maintenance_request' => $expense->maintenanceRequest ? [
                'id' => $expense->maintenanceRequest->id,
                'title' => $expense->maintenanceRequest->title,
                'status' => $expense->maintenanceRequest->status,
                'priority' => $expense->maintenanceRequest->priority,
            ] : null,
        ];
    }
}
