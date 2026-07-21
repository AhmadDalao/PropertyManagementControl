<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

class OperationsLeaseQuery
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    /**
     * @return array{
     *     expiringLeases:array<int, array<string, mixed>>,
     *     arrearsLeases:array<int, array<string, mixed>>
     * }
     */
    public function forUser(User $user): array
    {
        $leases = $this->portfolios->apply(Lease::query(), $user);

        return [
            'expiringLeases' => $this->expiring($leases),
            'arrearsLeases' => $this->arrears($leases),
        ];
    }

    /**
     * @param  Builder<Lease>  $leases
     * @return array<int, array<string, mixed>>
     */
    private function expiring(Builder $leases): array
    {
        return (clone $leases)
            ->with(['tenantProfile.user', 'leaseable', 'installments'])
            ->where('status', 'active')
            ->whereDate('ends_at', '<=', now()->addDays(90))
            ->orderBy('ends_at')
            ->limit(8)
            ->get()
            ->map(fn (Lease $lease): array => [
                'id' => $lease->id,
                'code' => $lease->code,
                'tenant' => $lease->tenantProfile?->user?->name,
                'asset' => $lease->leaseable?->getAttribute('title_en'),
                'ends_at' => $lease->ends_at?->toDateString(),
                'days_remaining' => $lease->days_remaining,
                'balance_remaining' => $lease->balance_remaining,
                'currency' => $lease->currency,
            ])
            ->all();
    }

    /**
     * @param  Builder<Lease>  $leases
     * @return array<int, array<string, mixed>>
     */
    private function arrears(Builder $leases): array
    {
        $overdue = LeaseInstallment::query()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN amount_due > amount_paid THEN amount_due - amount_paid ELSE 0 END), 0)'
            )
            ->whereColumn('lease_id', 'leases.id')
            ->whereDate('due_date', '<', today());

        return (clone $leases)
            ->with(['tenantProfile.user', 'leaseable'])
            ->whereIn('status', ['active', 'expired'])
            ->select('leases.*')
            ->selectSub($overdue, 'dashboard_overdue_amount')
            ->orderByDesc('dashboard_overdue_amount')
            ->limit(8)
            ->get()
            ->filter(fn (Lease $lease): bool => (float) $lease->getAttribute('dashboard_overdue_amount') > 0)
            ->values()
            ->map(fn (Lease $lease): array => [
                'id' => $lease->id,
                'code' => $lease->code,
                'tenant' => $lease->tenantProfile?->user?->name,
                'asset' => $lease->leaseable?->getAttribute('title_en'),
                'arrears_amount' => (float) $lease->getAttribute('dashboard_overdue_amount'),
                'currency' => $lease->currency,
            ])
            ->all();
    }
}
