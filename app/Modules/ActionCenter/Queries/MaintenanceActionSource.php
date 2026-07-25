<?php

namespace App\Modules\ActionCenter\Queries;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\ActionCenter\Contracts\ActionCenterSource;
use App\Modules\ActionCenter\Support\ActionCenterAssignee;
use App\Modules\ActionCenter\Support\ActionCenterItemState;
use App\Modules\Maintenance\Queries\MaintenanceDirectoryQuery;
use Illuminate\Database\Eloquent\Builder;

final readonly class MaintenanceActionSource implements ActionCenterSource
{
    public function __construct(
        private MaintenanceDirectoryQuery $directory,
        private ActionCenterAssignee $assignees,
        private ActionCenterItemState $itemState,
    ) {}

    public function type(): string
    {
        return 'maintenance';
    }

    public function module(): string
    {
        return 'maintenance';
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
            ->listing($this->query($actor, $filters), false)
            ->with('portfolio:id,name_en,name_ar')
            ->orderByRaw(
                "CASE WHEN priority = 'urgent' OR due_at < ? THEN 0 "
                ."WHEN priority = 'high' OR assigned_to_user_id IS NULL THEN 1 ELSE 2 END",
                [now()->toDateTimeString()],
            )
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->orderBy('requested_at')
            ->limit($limit)
            ->get()
            ->map(fn (MaintenanceRequest $request): array => $this->item($request))
            ->all());
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<MaintenanceRequest>
     */
    private function query(User $actor, array $filters): Builder
    {
        $query = $this->directory->managerBase($actor)
            ->whereIn('status', ['open', 'in_progress']);
        $this->directory->applyManagerFilters($query, [
            ...$filters,
            'status' => 'all',
            'category' => 'all',
            'priority' => 'all',
            'date_from' => '',
            'date_to' => '',
        ], $actor);
        $this->applyAssignee($query, $filters, $actor);
        $this->applyPriority($query, (string) ($filters['priority'] ?? 'all'));

        return $query;
    }

    /**
     * @param  Builder<MaintenanceRequest>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyAssignee(Builder $query, array $filters, User $actor): void
    {
        $assignee = $this->assignees->value($filters, $actor);

        if ($assignee === 'unassigned') {
            $query->whereNull('assigned_to_user_id');
        } elseif (is_int($assignee)) {
            $query->where('assigned_to_user_id', $assignee);
        }
    }

    /** @param Builder<MaintenanceRequest> $query */
    private function applyPriority(Builder $query, string $priority): void
    {
        match ($priority) {
            'critical' => $query->where(function (Builder $critical): void {
                $critical
                    ->where('priority', 'urgent')
                    ->orWhere('due_at', '<', now());
            }),
            'high' => $query
                ->where('priority', '!=', 'urgent')
                ->where(function (Builder $notOverdue): void {
                    $notOverdue
                        ->whereNull('due_at')
                        ->orWhere('due_at', '>=', now());
                })
                ->where(function (Builder $high): void {
                    $high
                        ->where('priority', 'high')
                        ->orWhereNull('assigned_to_user_id');
                }),
            'normal' => $query
                ->whereIn('priority', ['low', 'medium'])
                ->whereNotNull('assigned_to_user_id')
                ->where(function (Builder $notOverdue): void {
                    $notOverdue
                        ->whereNull('due_at')
                        ->orWhere('due_at', '>=', now());
                }),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function item(MaintenanceRequest $request): array
    {
        $priority = $request->priority === 'urgent' || $request->due_at?->isPast()
            ? 'critical'
            : ($request->priority === 'high' || $request->assigned_to_user_id === null
                ? 'high'
                : 'normal');

        return [
            'key' => 'maintenance:'.$request->id,
            'record_id' => $request->id,
            'type' => $this->type(),
            'priority' => $priority,
            'title' => $request->title,
            'subtitle' => trans("app.status.{$request->category}"),
            'tenant' => $request->tenantProfile?->user?->name,
            'asset' => $this->asset($request->asset),
            'portfolio' => $this->portfolio($request->portfolio),
            'status' => $request->status,
            'due_on' => $request->due_at?->toDateString(),
            'due_state' => $this->itemState->dueState($request->due_at),
            'opened_on' => $request->requested_at?->toDateString(),
            'assigned_to' => $request->assignedTo ? [
                'id' => $request->assignedTo->id,
                'name' => $request->assignedTo->name,
            ] : null,
            'amount' => null,
            'currency' => null,
            'href' => route('maintenance-requests.show', $request, false),
            'is_showcase' => $request->getIsShowcaseAttribute(),
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
