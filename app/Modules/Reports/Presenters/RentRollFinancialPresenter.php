<?php

namespace App\Modules\Reports\Presenters;

use App\Models\Lease;
use Illuminate\Support\Collection;

final class RentRollFinancialPresenter
{
    /**
     * @param  Collection<int, Lease>  $leases
     * @return list<array<string, float|int|string>>
     */
    public function present(Collection $leases): array
    {
        return array_values($leases
            ->groupBy(fn (Lease $lease): string => $lease->currency ?: 'SAR')
            ->map(function (Collection $group, string $currency): array {
                $contracted = (float) $group->sum(
                    fn (Lease $lease): float => (float) (
                        $lease->getAttribute('installments_total_due') ?? 0
                    ),
                );
                $paid = (float) $group->sum(
                    fn (Lease $lease): float => (float) (
                        $lease->getAttribute('installments_total_paid') ?? 0
                    ),
                );
                $overdue = (float) $group->sum(function (Lease $lease): float {
                    $due = (float) ($lease->getAttribute('installments_overdue_due') ?? 0);
                    $paid = (float) ($lease->getAttribute('installments_overdue_paid') ?? 0);

                    return max(0, $due - $paid);
                });

                return [
                    'currency' => $currency,
                    'active_leases' => $group->count(),
                    'contracted' => $contracted,
                    'paid' => $paid,
                    'outstanding' => max(0, $contracted - $paid),
                    'overdue' => $overdue,
                    'deposits' => (float) $group->sum('deposit_amount'),
                ];
            })
            ->sortKeys()
            ->values()
            ->all());
    }
}
