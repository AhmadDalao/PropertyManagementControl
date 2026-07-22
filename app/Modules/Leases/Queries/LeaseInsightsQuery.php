<?php

namespace App\Modules\Leases\Queries;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use Illuminate\Database\Eloquent\Builder;

final class LeaseInsightsQuery
{
    /**
     * @param  Builder<Lease>  $query
     * @return array<string, int|float>
     */
    public function get(Builder $query): array
    {
        $summary = (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count")
            ->selectRaw('SUM(CASE WHEN signed_at IS NULL THEN 1 ELSE 0 END) as unsigned_count')
            ->selectRaw("SUM(CASE WHEN status = 'active' AND ends_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring_count", [
                today()->toDateString(),
                today()->addDays(60)->toDateString(),
            ])
            ->first();
        $installments = LeaseInstallment::query()
            ->whereIn('lease_id', (clone $query)->select('leases.id'));
        $money = (clone $installments)
            ->selectRaw('COALESCE(SUM(amount_due), 0) as total_due')
            ->selectRaw('COALESCE(SUM(amount_paid), 0) as total_paid')
            ->first();
        $totalDue = (float) ($money?->getAttribute('total_due') ?? 0);
        $totalPaid = (float) ($money?->getAttribute('total_paid') ?? 0);

        return [
            'total' => (int) ($summary?->getAttribute('total') ?? 0),
            'active' => (int) ($summary?->getAttribute('active_count') ?? 0),
            'draft' => (int) ($summary?->getAttribute('draft_count') ?? 0),
            'unsigned' => (int) ($summary?->getAttribute('unsigned_count') ?? 0),
            'expiring_soon' => (int) ($summary?->getAttribute('expiring_count') ?? 0),
            'overdue' => (clone $query)->whereHas('installments', fn (Builder $installments) => $installments
                ->whereColumn('amount_paid', '<', 'amount_due')
                ->whereDate('due_date', '<', today()))->count(),
            'total_due' => $totalDue,
            'total_paid' => $totalPaid,
            'balance_remaining' => max(0, $totalDue - $totalPaid),
        ];
    }
}
