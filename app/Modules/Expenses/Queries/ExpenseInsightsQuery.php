<?php

namespace App\Modules\Expenses\Queries;

use App\Models\ExpenseEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class ExpenseInsightsQuery
{
    /**
     * @param  Builder<ExpenseEntry>  $baseQuery
     * @return array<string, int|float|string|null>
     */
    public function get(Builder $baseQuery): array
    {
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();
        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("SUM(CASE WHEN status = 'void' THEN 1 ELSE 0 END) as void_count")
            ->selectRaw('SUM(CASE WHEN asset_id IS NOT NULL THEN 1 ELSE 0 END) as linked_to_assets')
            ->selectRaw('SUM(CASE WHEN maintenance_request_id IS NOT NULL THEN 1 ELSE 0 END) as linked_to_maintenance')
            ->selectRaw('SUM(CASE WHEN asset_id IS NULL AND maintenance_request_id IS NULL THEN 1 ELSE 0 END) as unlinked_count')
            ->selectRaw("COUNT(DISTINCT CASE WHEN vendor_name IS NOT NULL AND vendor_name <> '' THEN vendor_name END) as vendors")
            ->first();
        $currencyRows = (clone $baseQuery)
            ->select('currency')
            ->selectRaw("SUM(CASE WHEN status = 'posted' THEN amount ELSE 0 END) as posted_amount")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount")
            ->selectRaw("SUM(CASE WHEN status = 'void' THEN amount ELSE 0 END) as void_amount")
            ->selectRaw("SUM(CASE WHEN status = 'posted' AND category = 'maintenance' THEN amount ELSE 0 END) as maintenance_amount")
            ->selectRaw(
                "SUM(CASE WHEN status = 'posted' AND incurred_on BETWEEN ? AND ? THEN amount ELSE 0 END) as posted_this_month",
                [$monthStart, $monthEnd],
            )
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();
        $currencyCount = $currencyRows->count();
        $currencyRow = $currencyCount <= 1 ? $currencyRows->first() : null;

        return [
            'total' => (int) ($summary?->getAttribute('total') ?? 0),
            'posted_count' => (int) ($summary?->getAttribute('posted_count') ?? 0),
            'pending_count' => (int) ($summary?->getAttribute('pending_count') ?? 0),
            'void_count' => (int) ($summary?->getAttribute('void_count') ?? 0),
            'posted_amount' => $this->amount($currencyRow, $currencyCount, 'posted_amount'),
            'pending_amount' => $this->amount($currencyRow, $currencyCount, 'pending_amount'),
            'void_amount' => $this->amount($currencyRow, $currencyCount, 'void_amount'),
            'maintenance_amount' => $this->amount($currencyRow, $currencyCount, 'maintenance_amount'),
            'posted_this_month' => $this->amount($currencyRow, $currencyCount, 'posted_this_month'),
            'linked_to_assets' => (int) ($summary?->getAttribute('linked_to_assets') ?? 0),
            'linked_to_maintenance' => (int) ($summary?->getAttribute('linked_to_maintenance') ?? 0),
            'unlinked_count' => (int) ($summary?->getAttribute('unlinked_count') ?? 0),
            'vendors' => (int) ($summary?->getAttribute('vendors') ?? 0),
            'currency' => $currencyRow ? (string) $currencyRow->currency : ($currencyCount === 0 ? 'SAR' : null),
            'currency_count' => $currencyCount,
        ];
    }

    private function amount(?ExpenseEntry $row, int $currencyCount, string $attribute): ?float
    {
        return $row ? (float) $row->getAttribute($attribute) : ($currencyCount === 0 ? 0.0 : null);
    }
}
