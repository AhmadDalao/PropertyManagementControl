<?php

namespace App\Modules\ActionCenter\Queries;

use App\Models\Asset;
use App\Models\LeaseMoveOut;
use App\Models\User;
use App\Modules\ActionCenter\Contracts\ActionCenterSource;
use App\Modules\ActionCenter\Support\ActionCenterAssignee;
use App\Modules\ActionCenter\Support\ActionCenterItemState;
use App\Modules\LeaseMoveOuts\Queries\LeaseMoveOutDirectoryQuery;
use Illuminate\Database\Eloquent\Builder;

final readonly class MoveOutActionSource implements ActionCenterSource
{
    public function __construct(
        private LeaseMoveOutDirectoryQuery $directory,
        private ActionCenterAssignee $assignees,
        private ActionCenterItemState $itemState,
    ) {}

    public function type(): string
    {
        return 'move_out';
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
            ->orderBy('move_out_date')
            ->limit($limit)
            ->get()
            ->map(fn (LeaseMoveOut $moveOut): array => $this->item($moveOut))
            ->all());
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<LeaseMoveOut>
     */
    private function query(User $actor, array $filters): Builder
    {
        $query = $this->directory->base($actor)->where('status', 'planned');
        $this->directory->apply($query, [
            ...$filters,
            'queue' => 'all',
            'horizon' => '90',
        ], $actor);
        $this->applyAssignee($query, $filters, $actor);
        $this->applyPriority($query, (string) ($filters['priority'] ?? 'all'));

        return $query;
    }

    /**
     * @param  Builder<LeaseMoveOut>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyAssignee(Builder $query, array $filters, User $actor): void
    {
        $assignee = $this->assignees->value($filters, $actor);

        if ($assignee === 'unassigned') {
            $query->whereHas(
                'lease',
                fn (Builder $leases) => $leases->whereNull('managed_by_user_id'),
            );
        } elseif (is_int($assignee)) {
            $query->whereHas(
                'lease',
                fn (Builder $leases) => $leases
                    ->where('managed_by_user_id', $assignee),
            );
        }
    }

    /** @param Builder<LeaseMoveOut> $query */
    private function applyPriority(Builder $query, string $priority): void
    {
        match ($priority) {
            'critical' => $query->whereDate('move_out_date', '<', today()),
            'high' => $query
                ->whereDate('move_out_date', '>=', today())
                ->whereDate('move_out_date', '<=', today()->addDays(7)),
            'normal' => $query->whereDate('move_out_date', '>', today()->addDays(7)),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function item(LeaseMoveOut $moveOut): array
    {
        $lease = $moveOut->lease;
        $asset = $lease?->leaseable instanceof Asset ? $lease->leaseable : null;
        $date = $moveOut->move_out_date;
        $priority = $date?->isPast() && ! $date->isToday()
            ? 'critical'
            : ($date?->lessThanOrEqualTo(today()->addDays(7)) ? 'high' : 'normal');
        $documents = $lease->documents;
        $ready = $date?->lessThanOrEqualTo(today())
            && $moveOut->keys_returned
            && $moveOut->deposit_disposition !== 'pending'
            && $documents->contains('type', 'termination_notice')
            && $documents->contains('type', 'move_out_inspection');
        $state = $ready
            ? 'ready'
            : ($date?->isToday()
                ? 'due_today'
                : ($date?->isPast() ? 'overdue' : 'scheduled'));

        return [
            'key' => 'move_out:'.$moveOut->id,
            'record_id' => $moveOut->id,
            'type' => $this->type(),
            'priority' => $priority,
            'title' => $lease?->code ?: '#'.$moveOut->id,
            'subtitle' => $moveOut->reason,
            'tenant' => $lease?->tenantProfile?->user?->name,
            'asset' => $this->asset($asset),
            'portfolio' => $this->portfolio($lease?->portfolio),
            'status' => $state,
            'due_on' => $date?->toDateString(),
            'due_state' => $this->itemState->dueState($date),
            'assigned_to' => $lease?->managedBy ? [
                'id' => $lease->managedBy->id,
                'name' => $lease->managedBy->name,
            ] : null,
            'amount' => null,
            'currency' => $lease?->currency ?: 'SAR',
            'href' => $lease
                ? route('leases.show', $lease, false)
                : route('lease-move-outs.index', absolute: false),
            'is_showcase' => $lease?->getIsShowcaseAttribute() ?? false,
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
