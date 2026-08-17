<?php

namespace App\Modules\Expenses\Presenters;

use App\Modules\Expenses\Data\ExpenseFormData;

final class ExpenseFormContextPresenter
{
    /** @return array{portfolio:string|null,workOrderId:int|null} */
    public function present(ExpenseFormData $data): array
    {
        $portfolio = collect($data->portfolios)->firstWhere('value', $data->portfolioId);
        $metadata = [];

        if ($data->expense !== null) {
            $metadata = $data->expense->meta_json ?? [];
        }
        $linkedWorkOrderId = $metadata['maintenance_work_order_id'] ?? null;
        $workOrderId = $linkedWorkOrderId
            ?? $data->defaults['maintenance_work_order_id']
            ?? null;

        return [
            'portfolio' => is_array($portfolio) ? $portfolio['label'] : null,
            'workOrderId' => filter_var(
                $workOrderId,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]],
            ) ?: null,
        ];
    }
}
