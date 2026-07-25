<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Dashboard\Support\DashboardPropertyContext;
use App\Modules\Shared\PortfolioScope;

final readonly class OperationsCollectionQuery
{
    public function __construct(private PortfolioScope $portfolios) {}

    /** @return array<int, array<string, mixed>> */
    public function forUser(User $actor, DashboardPropertyContext $context): array
    {
        $leaseIds = $context
            ->leases($this->portfolios->apply(Lease::query(), $actor))
            ->whereIn('status', ['active', 'expired'])
            ->select('id');

        return LeaseInstallment::query()
            ->whereIn('lease_id', $leaseIds)
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->whereDate('due_date', '<=', now()->addDays(30))
            ->with(['lease.tenantProfile.user', 'lease.leaseable'])
            ->orderBy('due_date')
            ->limit(8)
            ->get()
            ->map(function (LeaseInstallment $installment): array {
                $lease = $installment->lease;
                $asset = $lease?->leaseable instanceof Asset ? $lease->leaseable : null;

                return [
                    'id' => $installment->id,
                    'lease_id' => $lease?->id,
                    'lease_code' => $lease?->code,
                    'tenant' => $lease?->tenantProfile?->user?->name,
                    'asset_en' => $asset?->title_en,
                    'asset_ar' => $asset?->title_ar,
                    'due_date' => $installment->due_date?->toDateString(),
                    'outstanding_amount' => $installment->remaining_amount,
                    'days_overdue' => $installment->due_date?->isBefore(today())
                        ? (int) $installment->due_date->diffInDays(today())
                        : 0,
                    'currency' => $lease->currency ?: 'SAR',
                ];
            })
            ->all();
    }
}
