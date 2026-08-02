<?php

namespace App\Modules\ActionCenter\Presenters;

use App\Models\User;
use App\Modules\ActionCenter\Queries\ActionCenterAssigneeQuery;
use App\Modules\ActionCenter\Support\ActionCenterOptions;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\Shared\PortfolioScope;

final readonly class ActionCenterReportPresenter
{
    public function __construct(
        private PortfolioScope $portfolios,
        private PropertyScope $properties,
        private ActionCenterAssigneeQuery $assignees,
    ) {}

    /**
     * @param array{
     *     search:string,type:string,priority:string,assignee:string,
     *     portfolio_id:int|null,property_id:int|null,per_page:int,page:int
     * } $filters
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public function present(User $actor, array $filters, array $items): array
    {
        return [
            'records' => $items,
            'summary' => $this->summary($items),
            'typePositions' => $this->typePositions($items),
            'currencyPositions' => $this->currencyPositions($items),
            'scope' => $this->scope($actor, $filters),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{total:int,critical:int,high:int,normal:int,unassigned:int}
     */
    private function summary(array $items): array
    {
        $summary = [
            'total' => count($items),
            'critical' => 0,
            'high' => 0,
            'normal' => 0,
            'unassigned' => 0,
        ];

        foreach ($items as $item) {
            match ($item['priority']) {
                'critical' => $summary['critical']++,
                'high' => $summary['high']++,
                default => $summary['normal']++,
            };

            if (($item['assigned_to'] ?? null) === null) {
                $summary['unassigned']++;
            }
        }

        return $summary;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<int, array{type:string,count:int}>
     */
    private function typePositions(array $items): array
    {
        $counts = array_fill_keys(ActionCenterOptions::TYPES, 0);

        foreach ($items as $item) {
            $counts[(string) $item['type']]++;
        }

        return array_map(
            static fn (string $type): array => [
                'type' => $type,
                'count' => $counts[$type],
            ],
            ActionCenterOptions::TYPES,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<int, array{currency:string,count:int,amount:float}>
     */
    private function currencyPositions(array $items): array
    {
        $positions = [];

        foreach ($items as $item) {
            if (! is_numeric($item['amount'] ?? null) || empty($item['currency'])) {
                continue;
            }

            $currency = (string) $item['currency'];
            $positions[$currency] ??= [
                'currency' => $currency,
                'count' => 0,
                'amount' => 0.0,
            ];
            $positions[$currency]['count']++;
            $positions[$currency]['amount'] += (float) $item['amount'];
        }

        ksort($positions);

        return array_values($positions);
    }

    /**
     * @param array{
     *     search:string,type:string,priority:string,assignee:string,
     *     portfolio_id:int|null,property_id:int|null,per_page:int,page:int
     * } $filters
     * @return array<int, array{label:string,value:string}>
     */
    private function scope(User $actor, array $filters): array
    {
        $portfolioOptions = $this->portfolios->options($actor);
        $propertyOptions = $this->properties->options($actor);
        $portfolio = collect($portfolioOptions)->firstWhere('id', $filters['portfolio_id']);
        $property = collect($propertyOptions)->firstWhere('id', $filters['property_id']);
        $scope = [
            [
                'label' => trans('app.reports.scope_current'),
                'value' => today()->toDateString(),
            ],
            [
                'label' => trans('app.reports.scope_portfolio'),
                'value' => (string) ($portfolio['name']
                    ?? (count($portfolioOptions) === 1
                        ? $portfolioOptions[0]['name']
                        : trans('app.reports.all_portfolios'))),
            ],
            [
                'label' => trans('app.reports.scope_property'),
                'value' => (string) ($property['name'] ?? trans('app.reports.all_properties')),
            ],
            [
                'label' => trans('app.action_center.type_filter'),
                'value' => trans('app.action_center.type_'.$filters['type']),
            ],
            [
                'label' => trans('app.action_center.priority'),
                'value' => trans('app.action_center.priority_'.$filters['priority']),
            ],
            [
                'label' => trans('app.action_center.assignee'),
                'value' => $this->assignee($actor, $filters),
            ],
        ];

        if ($filters['search'] !== '') {
            $scope[] = [
                'label' => trans('app.action_center.search'),
                'value' => $filters['search'],
            ];
        }

        return $scope;
    }

    /**
     * @param array{
     *     search:string,type:string,priority:string,assignee:string,
     *     portfolio_id:int|null,property_id:int|null,per_page:int,page:int
     * } $filters
     */
    private function assignee(User $actor, array $filters): string
    {
        return match ($filters['assignee']) {
            'all' => trans('app.action_center.assignee_all'),
            'me' => trans('app.action_center.assignee_me').' · '.$actor->name,
            'unassigned' => trans('app.action_center.assignee_unassigned'),
            default => (string) (
                collect($this->assignees->options($actor, $filters['portfolio_id']))
                    ->firstWhere('id', (int) $filters['assignee'])['label']
                ?? trans('app.action_center.assignee_all')
            ),
        };
    }
}
