<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Queries\PropertyExplorerMetricsQuery;
use App\Modules\Assets\Queries\PropertyExplorerRecordQuery;
use App\Modules\Assets\Queries\PropertyExplorerSelectionQuery;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\PortfolioScope;

final readonly class PropertyExplorerPresenter
{
    public function __construct(
        private PropertyExplorerSelectionQuery $selection,
        private PropertyExplorerRecordQuery $records,
        private PropertyExplorerMetricsQuery $metrics,
        private PropertyExplorerAssetPresenter $assets,
        private PortfolioScope $portfolios,
    ) {}

    /**
     * @param  array{
     *     property_id:int|null,
     *     node_id:int|null,
     *     search:string,
     *     asset_type:string,
     *     occupancy_status:string,
     *     page:int
     * }  $filters
     * @return array<string, mixed>
     */
    public function present(User $actor, array $filters): array
    {
        $selection = $this->selection->resolve($actor, $filters);
        $root = $selection['root'];
        $node = $selection['node'];
        $properties = $selection['roots']->map(fn (Asset $property): array => [
            'id' => $property->id,
            'portfolio_id' => $property->portfolio_id,
            'code' => $property->code,
            'title_en' => $property->title_en,
            'title_ar' => $property->title_ar,
            'portfolio' => $this->portfolios->localized(
                $property->portfolio?->name_en,
                $property->portfolio?->name_ar,
            ),
        ])->values()->all();

        if (! $root instanceof Asset || ! $node instanceof Asset) {
            return [
                'filters' => $filters,
                'properties' => $properties,
                'selected' => null,
                'breadcrumbs' => [],
                'metrics' => [],
                'records' => null,
                'active_lease' => null,
                'modules' => $this->modules($actor),
            ];
        }

        $modules = $this->modules($actor);
        $records = $this->records->paginate($node, $selection['allowed_ids'], $filters);
        $records->through(fn (Asset $asset): array => $this->assets->record(
            $asset,
            $root->id,
            $modules['leases'],
        ));
        $selected = $this->assets->selected($node, $root->id, $modules['leases']);

        return [
            'filters' => [...$filters, 'property_id' => $root->id, 'node_id' => $node->id],
            'properties' => $properties,
            'selected' => $selected,
            'breadcrumbs' => collect($selection['breadcrumbs'])
                ->map(fn (Asset $asset): array => [
                    'id' => $asset->id,
                    'title_en' => $asset->title_en,
                    'title_ar' => $asset->title_ar,
                    'code' => $asset->code,
                    'href' => route('property-explorer.index', [
                        'property_id' => $root->id,
                        'node_id' => $asset->id,
                    ]),
                ])
                ->values()
                ->all(),
            'metrics' => $this->metrics->forNode($node, $selection['allowed_ids']),
            'records' => $records,
            'active_lease' => $selected['lease'],
            'modules' => $modules,
        ];
    }

    /** @return array<string, bool> */
    private function modules(User $actor): array
    {
        return [
            'tenants' => PortfolioModules::enabledForUser($actor, 'tenants'),
            'leases' => PortfolioModules::enabledForUser($actor, 'leases'),
            'payments' => PortfolioModules::enabledForUser($actor, 'payments'),
            'maintenance' => PortfolioModules::enabledForUser($actor, 'maintenance'),
        ];
    }
}
