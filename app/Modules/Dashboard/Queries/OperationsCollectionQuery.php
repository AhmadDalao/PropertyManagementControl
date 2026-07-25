<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Dashboard\Support\DashboardPropertyContext;
use App\Modules\RentCollection\Support\CollectionFollowUpState;
use App\Modules\Shared\PortfolioScope;

final readonly class OperationsCollectionQuery
{
    public function __construct(
        private PortfolioScope $portfolios,
        private CollectionFollowUpState $followUpState,
    ) {}

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
            ->with([
                'lease.tenantProfile.user',
                'lease.leaseable',
                'latestCollectionFollowUp.assignedTo:id,name',
            ])
            ->orderBy('due_date')
            ->limit(24)
            ->get()
            ->map(function (LeaseInstallment $installment): array {
                $lease = $installment->lease;
                $asset = $lease?->leaseable instanceof Asset ? $lease->leaseable : null;
                $followUp = $installment->latestCollectionFollowUp;

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
                    'follow_up_state' => $this->followUpState->resolve($installment, $followUp),
                    'next_follow_up_on' => $followUp?->next_follow_up_on?->toDateString(),
                    'promised_on' => $followUp?->promised_on?->toDateString(),
                    'assigned_to' => $followUp?->assignedTo?->name,
                ];
            })
            ->sort(function (array $left, array $right): int {
                $priority = $this->followUpState->priority($left['follow_up_state'])
                    <=> $this->followUpState->priority($right['follow_up_state']);

                return $priority !== 0
                    ? $priority
                    : ((string) ($left['due_date'] ?? '') <=> (string) ($right['due_date'] ?? ''));
            })
            ->take(8)
            ->values()
            ->all();
    }
}
