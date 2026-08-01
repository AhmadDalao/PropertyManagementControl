<?php

namespace App\Modules\Assets\Presenters;

use App\Modules\Assets\Data\AssetOperationsData;

final class AssetCurrencySummaryPresenter
{
    public function money(AssetOperationsData $operations, string $field): string
    {
        return collect($operations->currencyTotals)
            ->map(fn (array $total): string => number_format(
                (float) ($total[$field] ?? 0),
                2,
            ).' '.$total['currency'])
            ->implode(' · ');
    }

    public function collectionRate(AssetOperationsData $operations): string
    {
        return collect($operations->currencyTotals)
            ->map(fn (array $total): string => (
                count($operations->currencyTotals) > 1
                    ? $total['currency'].' '
                    : ''
            ).number_format((float) $total['collectionRate'], 1).'%')
            ->implode(' · ');
    }

    public function countLabel(AssetOperationsData $operations): string
    {
        return trans('app.assets.currency_positions_count', [
            'count' => count($operations->currencyTotals),
        ]);
    }
}
