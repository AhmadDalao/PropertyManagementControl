<?php

namespace App\Modules\ActionCenter\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\ActionCenter\Contracts\ActionCenterSource;
use App\Modules\ActionCenter\Support\ActionCenterAssignee;
use App\Modules\ActionCenter\Support\ActionCenterItemState;
use App\Modules\LeaseRenewals\Queries\LeaseRenewalDirectoryQuery;
use Illuminate\Database\Eloquent\Builder;

final readonly class RenewalActionSource implements ActionCenterSource
{
    public function __construct(
        private LeaseRenewalDirectoryQuery $directory,
        private ActionCenterAssignee $assignees,
        private ActionCenterItemState $itemState,
    ) {}

    public function type(): string
    {
        return 'renewal';
    }

    public function module(): string
    {
        return 'leases';
    }

    public function count(User $actor, array $filters): int
    {
        return $this->query($actor, $filters)->count();
    }

    public function items(User $actor, array $filters, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        return array_values($this->directory
            ->listing($this->query($actor, $filters))
            ->orderBy('ends_at')
            ->limit($limit)
            ->get()
            ->map(fn (Lease $lease): array => $this->item($lease))
            ->all());
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Lease>
     */
    private function query(User $actor, array $filters): Builder
    {
        $query = $this->directory->base($actor);
        $this->directory->apply($query, [
            ...$filters,
            'queue' => 'attention',
            'horizon' => 'all',
            'lease_status' => 'all',
        ], $actor);
        $this->applyAssignee($query, $filters, $actor);
        $this->applyPriority($query, (string) ($filters['priority'] ?? 'all'));

        return $query;
    }

    /**
     * @param  Builder<Lease>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyAssignee(Builder $query, array $filters, User $actor): void
    {
        $assignee = $this->assignees->value($filters, $actor);

        if ($assignee === 'unassigned') {
            $query->whereNull('managed_by_user_id');
        } elseif (is_int($assignee)) {
            $query->where('managed_by_user_id', $assignee);
        }
    }

    /** @param Builder<Lease> $query */
    private function applyPriority(Builder $query, string $priority): void
    {
        match ($priority) {
            'critical' => $query->where(function (Builder $critical): void {
                $critical
                    ->where('status', 'expired')
                    ->orWhereDate('ends_at', '<=', today()->addDays(30));
            }),
            'high' => $query
                ->where('status', 'active')
                ->whereDate('ends_at', '>', today()->addDays(30))
                ->whereDate('ends_at', '<=', today()->addDays(60)),
            'normal' => $query
                ->where('status', 'active')
                ->whereDate('ends_at', '>', today()->addDays(60)),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function item(Lease $lease): array
    {
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;
        $priority = $lease->status === 'expired' || $lease->ends_at?->lessThanOrEqualTo(today()->addDays(30))
            ? 'critical'
            : ($lease->ends_at?->lessThanOrEqualTo(today()->addDays(60))
                ? 'high'
                : 'normal');
        $totalDue = (float) $lease->getAttribute('installments_total_due');
        $totalPaid = (float) $lease->getAttribute('installments_total_paid');

        return [
            'key' => 'renewal:'.$lease->id,
            'record_id' => $lease->id,
            'type' => $this->type(),
            'priority' => $priority,
            'title' => $lease->code,
            'subtitle' => trans("app.status.{$lease->status}"),
            'tenant' => $lease->tenantProfile?->user?->name,
            'asset' => $this->asset($asset),
            'portfolio' => $this->portfolio($lease->portfolio),
            'status' => $lease->status === 'expired' ? 'expired' : 'notice_due',
            'due_on' => $lease->ends_at?->toDateString(),
            'due_state' => $this->itemState->dueState($lease->ends_at),
            'assigned_to' => $lease->managedBy ? [
                'id' => $lease->managedBy->id,
                'name' => $lease->managedBy->name,
            ] : null,
            'amount' => max(0, $totalDue - $totalPaid),
            'currency' => $lease->currency ?: 'SAR',
            'href' => route('leases.show', $lease, false),
            'is_showcase' => $lease->getIsShowcaseAttribute(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function asset(?Asset $asset): ?array
    {
        return $asset ? [
            'id' => $asset->id,
            'title_en' => $asset->title_en,
            'title_ar' => $asset->title_ar,
            'code' => $asset->code,
        ] : null;
    }

    /** @return array<string, mixed>|null */
    private function portfolio(mixed $portfolio): ?array
    {
        return $portfolio ? [
            'id' => $portfolio->id,
            'name_en' => $portfolio->name_en,
            'name_ar' => $portfolio->name_ar,
        ] : null;
    }
}
