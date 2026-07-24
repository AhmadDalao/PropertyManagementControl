<?php

namespace App\Modules\RentCollection\Queries;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use Illuminate\Database\Eloquent\Builder;

final class RentCollectionInsightsQuery
{
    /**
     * @param  Builder<LeaseInstallment>  $query
     * @return array<string, int|float|string|bool|null>
     */
    public function get(Builder $query): array
    {
        $open = (clone $query)->whereColumn('amount_paid', '<', 'amount_due');
        $overdue = (clone $open)->whereDate('due_date', '<', today());
        $nextThirtyDays = (clone $open)->whereBetween('due_date', [
            today(),
            today()->addDays(30),
        ]);
        $thisMonth = (clone $query)->whereBetween('due_date', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
        $scheduledThisMonth = (float) (clone $thisMonth)->sum('amount_due');
        $paidThisMonth = (float) (clone $thisMonth)->sum('amount_paid');
        $currencies = Lease::query()
            ->whereIn('id', (clone $query)->select('lease_id'))
            ->distinct()
            ->pluck('currency')
            ->filter()
            ->values();

        return [
            'open_count' => (clone $open)->count(),
            'overdue_count' => (clone $overdue)->count(),
            'outstanding_amount' => $this->remaining($open),
            'overdue_amount' => $this->remaining($overdue),
            'due_next_30_amount' => $this->remaining($nextThirtyDays),
            'scheduled_this_month' => $scheduledThisMonth,
            'paid_this_month' => $paidThisMonth,
            'collection_rate' => $scheduledThisMonth > 0
                ? round(min(100, ($paidThisMonth / $scheduledThisMonth) * 100), 1)
                : 0.0,
            'currency' => $currencies->count() === 1 ? (string) $currencies->first() : 'SAR',
            'mixed_currencies' => $currencies->count() > 1,
        ];
    }

    /** @param Builder<LeaseInstallment> $query */
    private function remaining(Builder $query): float
    {
        return (float) $query
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN amount_due > amount_paid THEN amount_due - amount_paid ELSE 0 END), 0) AS remaining_total',
            )
            ->value('remaining_total');
    }
}
