<?php

namespace App\Modules\ActionCenter\Queries;

use App\Models\Asset;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\ActionCenter\Contracts\ActionCenterSource;
use App\Modules\ActionCenter\Support\ActionCenterAssignee;
use App\Modules\ActionCenter\Support\ActionCenterItemState;
use App\Modules\Leases\Presenters\LeaseInstallmentLabelPresenter;
use App\Modules\RentCollection\Queries\RentCollectionDirectoryQuery;
use App\Modules\RentCollection\Support\CollectionFollowUpQueryState;
use App\Modules\RentCollection\Support\CollectionFollowUpState;
use Illuminate\Database\Eloquent\Builder;

final readonly class CollectionActionSource implements ActionCenterSource
{
    public function __construct(
        private RentCollectionDirectoryQuery $directory,
        private CollectionFollowUpQueryState $queryState,
        private CollectionFollowUpState $followUpState,
        private LeaseInstallmentLabelPresenter $labels,
        private ActionCenterAssignee $assignees,
        private ActionCenterItemState $itemState,
    ) {}

    public function type(): string
    {
        return 'collection';
    }

    public function module(): string
    {
        return 'payments';
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

        $candidateLimit = min(max($limit * 3, $limit), 25_000);
        $query = $this->directory->listing($this->query($actor, $filters))
            ->with('lease.portfolio:id,name_en,name_ar')
            ->orderBy('due_date')
            ->limit($candidateLimit);

        return array_values($query
            ->get()
            ->map(fn (LeaseInstallment $installment): array => $this->item($installment))
            ->sort($this->compare(...))
            ->take($limit)
            ->all());
    }

    /** @param array<string, mixed> $filters
     * @return Builder<LeaseInstallment>
     */
    private function query(User $actor, array $filters): Builder
    {
        $query = $this->directory->base($actor);
        $this->directory->apply($query, [
            ...$filters,
            'status' => 'actionable',
            'line_type' => 'all',
            'follow_up' => 'all',
            'date_from' => '',
            'date_to' => '',
            'sort' => 'due_date',
            'direction' => 'asc',
        ], $actor);
        $this->applyAssignee($query, $filters, $actor);
        $this->applyPriority($query, (string) ($filters['priority'] ?? 'all'));

        return $query;
    }

    /**
     * @param  Builder<LeaseInstallment>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyAssignee(Builder $query, array $filters, User $actor): void
    {
        $assignee = $this->assignees->value($filters, $actor);

        if ($assignee === null) {
            return;
        }

        if ($assignee === 'unassigned') {
            $query->where(function (Builder $installments): void {
                $installments
                    ->whereDoesntHave('latestCollectionFollowUp')
                    ->orWhereHas(
                        'latestCollectionFollowUp',
                        fn (Builder $followUps) => $followUps
                            ->whereNull('assigned_to_user_id'),
                    );
            });

            return;
        }

        $query->whereHas(
            'latestCollectionFollowUp',
            fn (Builder $followUps) => $followUps
                ->where('assigned_to_user_id', $assignee),
        );
    }

    /** @param Builder<LeaseInstallment> $query */
    private function applyPriority(Builder $query, string $priority): void
    {
        match ($priority) {
            'critical' => $query->where(fn (Builder $items) => $this->critical($items)),
            'high' => $query
                ->where(function (Builder $notCritical): void {
                    $notCritical
                        ->whereDate('due_date', '>', today()->subDays(30))
                        ->whereDoesntHave(
                            'latestCollectionFollowUp',
                            fn (Builder $followUps) => $this->queryState->broken($followUps),
                        );
                })
                ->where(function (Builder $high): void {
                    $high
                        ->whereDate('due_date', '<', today())
                        ->orWhereDoesntHave('latestCollectionFollowUp')
                        ->orWhereHas(
                            'latestCollectionFollowUp',
                            fn (Builder $followUps) => $followUps
                                ->whereDate('next_follow_up_on', '<=', today()),
                        );
                }),
            'normal' => $query
                ->whereDate('due_date', '>=', today())
                ->whereHas(
                    'latestCollectionFollowUp',
                    fn (Builder $followUps) => $followUps
                        ->whereDate('next_follow_up_on', '>', today()),
                )
                ->whereDoesntHave(
                    'latestCollectionFollowUp',
                    fn (Builder $followUps) => $this->queryState->broken($followUps),
                ),
            default => null,
        };
    }

    /** @param Builder<LeaseInstallment> $query */
    private function critical(Builder $query): void
    {
        $query
            ->whereDate('due_date', '<=', today()->subDays(30))
            ->orWhereHas(
                'latestCollectionFollowUp',
                fn (Builder $followUps) => $this->queryState->broken($followUps),
            );
    }

    /** @return array<string, mixed> */
    private function item(LeaseInstallment $installment): array
    {
        $lease = $installment->lease;
        $asset = $lease?->leaseable instanceof Asset ? $lease->leaseable : null;
        $followUp = $installment->latestCollectionFollowUp;
        $state = $this->followUpState->resolve($installment, $followUp);
        $daysOverdue = $installment->due_date?->isBefore(today())
            ? (int) $installment->due_date->diffInDays(today())
            : 0;
        $priority = $state === 'broken' || $daysOverdue >= 30
            ? 'critical'
            : ($daysOverdue > 0 || in_array($state, ['due', 'untracked'], true)
                ? 'high'
                : 'normal');
        $dueOn = match ($state) {
            'broken' => $followUp?->promised_on,
            'due', 'scheduled' => $followUp?->next_follow_up_on,
            'promised' => $followUp?->promised_on,
            default => $installment->due_date,
        } ?? $installment->due_date;

        return [
            'key' => 'collection:'.$installment->id,
            'record_id' => $installment->id,
            'type' => $this->type(),
            'priority' => $priority,
            'title' => $lease?->code ?: $this->labels->present($installment),
            'subtitle' => $this->labels->present($installment),
            'tenant' => $lease?->tenantProfile?->user?->name,
            'asset' => $this->asset($asset),
            'portfolio' => $this->portfolio($lease?->portfolio),
            'status' => $state,
            'due_on' => $dueOn?->toDateString(),
            'due_state' => $this->itemState->dueState($dueOn),
            'assigned_to' => $followUp?->assignedTo ? [
                'id' => $followUp->assignedTo->id,
                'name' => $followUp->assignedTo->name,
            ] : null,
            'amount' => $installment->remaining_amount,
            'currency' => $lease?->currency ?: 'SAR',
            'href' => route('rent-collection.follow-up', $installment, false),
            'is_showcase' => $lease?->getIsShowcaseAttribute() ?? false,
        ];
    }

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     */
    private function compare(array $left, array $right): int
    {
        return $this->itemState->rank((string) $left['priority'])
            <=> $this->itemState->rank((string) $right['priority'])
            ?: ((string) ($left['due_on'] ?? '9999-12-31')
                <=> (string) ($right['due_on'] ?? '9999-12-31'))
            ?: ((int) $left['record_id'] <=> (int) $right['record_id']);
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
