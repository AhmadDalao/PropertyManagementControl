<?php

namespace App\Modules\ActionCenter\Queries;

use App\Models\User;
use App\Modules\ActionCenter\Contracts\ActionCenterSource;
use App\Modules\ActionCenter\Support\ActionCenterAccess;
use App\Modules\ActionCenter\Support\ActionCenterItemState;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ActionCenterIndexQuery
{
    public function __construct(
        private ActionCenterAccess $access,
        private CollectionActionSource $collections,
        private MaintenanceActionSource $maintenance,
        private RenewalActionSource $renewals,
        private MoveOutActionSource $moveOuts,
        private ActionCenterAssigneeQuery $assignees,
        private ActionCenterItemState $itemState,
        private PortfolioScope $portfolios,
        private PropertyScope $properties,
    ) {}

    /**
     * @param array{
     *     search:string,type:string,priority:string,assignee:string,
     *     portfolio_id:int|null,property_id:int|null,per_page:int,page:int
     * } $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, array $filters): array
    {
        $this->access->ensureCanView($actor);
        $available = $this->availableSources($actor);
        $selected = $this->selectedSources($available, $filters['type']);
        $total = $this->total($selected, $actor, $filters);
        $limit = min($filters['page'] * $filters['per_page'], 25_000);
        $items = $this->sortedItems($selected, $actor, $filters, $limit);
        $offset = ($filters['page'] - 1) * $filters['per_page'];
        $pageItems = array_slice($items, $offset, $filters['per_page']);
        $paginator = new LengthAwarePaginator(
            $pageItems,
            $total,
            $filters['per_page'],
            $filters['page'],
            [
                'path' => route('action-center.index'),
                'query' => request()->query(),
            ],
        );
        $metricFilters = [
            ...$filters,
            'priority' => 'all',
            'assignee' => 'all',
        ];

        return [
            'actionItems' => $paginator,
            'filters' => $filters,
            'metrics' => [
                'total' => $this->total($selected, $actor, $metricFilters),
                'critical' => $this->total(
                    $selected,
                    $actor,
                    [...$metricFilters, 'priority' => 'critical'],
                ),
                'high' => $this->total(
                    $selected,
                    $actor,
                    [...$metricFilters, 'priority' => 'high'],
                ),
                'unassigned' => $this->total(
                    $selected,
                    $actor,
                    [...$metricFilters, 'assignee' => 'unassigned'],
                ),
            ],
            'counts' => $this->typeCounts($available, $actor, $filters),
            'portfolioOptions' => $this->portfolios->options($actor),
            'propertyOptions' => $this->properties->options($actor),
            'assigneeOptions' => $this->assignees->options(
                $actor,
                $filters['portfolio_id'],
            ),
        ];
    }

    /**
     * @param array{
     *     search:string,type:string,priority:string,assignee:string,
     *     portfolio_id:int|null,property_id:int|null,per_page:int,page:int
     * } $filters
     * @return list<array<string, mixed>>
     */
    public function exportItems(User $actor, array $filters): array
    {
        $this->access->ensureCanView($actor);
        $sources = $this->selectedSources(
            $this->availableSources($actor),
            $filters['type'],
        );
        $total = $this->total($sources, $actor, $filters);

        abort_if(
            $total > 25_000,
            422,
            trans('app.errors.action_center_export_too_large'),
        );

        return $this->sortedItems($sources, $actor, $filters, $total);
    }

    /** @return list<ActionCenterSource> */
    private function availableSources(User $actor): array
    {
        return array_values(array_filter(
            $this->sources(),
            fn (ActionCenterSource $source): bool => PortfolioModules::enabledForUser(
                $actor,
                $source->module(),
            ),
        ));
    }

    /**
     * @param  list<ActionCenterSource>  $sources
     * @return list<ActionCenterSource>
     */
    private function selectedSources(array $sources, string $type): array
    {
        return $type === 'all'
            ? $sources
            : array_values(array_filter(
                $sources,
                fn (ActionCenterSource $source): bool => $source->type() === $type,
            ));
    }

    /**
     * @param  list<ActionCenterSource>  $sources
     * @param  array<string, mixed>  $filters
     */
    private function total(array $sources, User $actor, array $filters): int
    {
        return array_sum(array_map(
            fn (ActionCenterSource $source): int => $source->count($actor, $filters),
            $sources,
        ));
    }

    /**
     * @param  list<ActionCenterSource>  $sources
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function sortedItems(
        array $sources,
        User $actor,
        array $filters,
        int $limit,
    ): array {
        $items = [];

        foreach ($sources as $source) {
            $items = [
                ...$items,
                ...$source->items($actor, $filters, $limit),
            ];
        }

        usort($items, function (array $left, array $right): int {
            return $this->itemState->rank((string) $left['priority'])
                <=> $this->itemState->rank((string) $right['priority'])
                ?: ((string) ($left['due_on'] ?? '9999-12-31')
                    <=> (string) ($right['due_on'] ?? '9999-12-31'))
                ?: ((string) $left['key'] <=> (string) $right['key']);
        });

        return array_slice($items, 0, $limit);
    }

    /**
     * @param  list<ActionCenterSource>  $sources
     * @param  array<string, mixed>  $filters
     * @return list<array{type:string,value:int,active:bool}>
     */
    private function typeCounts(
        array $sources,
        User $actor,
        array $filters,
    ): array {
        $counts = [[
            'type' => 'all',
            'value' => $this->total($sources, $actor, $filters),
            'active' => $filters['type'] === 'all',
        ]];

        foreach ($sources as $source) {
            $counts[] = [
                'type' => $source->type(),
                'value' => $source->count($actor, $filters),
                'active' => $filters['type'] === $source->type(),
            ];
        }

        return $counts;
    }

    /** @return list<ActionCenterSource> */
    private function sources(): array
    {
        return [
            $this->collections,
            $this->maintenance,
            $this->renewals,
            $this->moveOuts,
        ];
    }
}
