<?php

namespace App\Modules\LeaseMoveOuts\Queries;

use App\Models\Asset;
use App\Models\LeaseMoveOut;
use App\Models\User;
use App\Modules\Assets\Support\AssetPropertyContext;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\LeaseMoveOuts\Presenters\LeaseMoveOutRowPresenter;
use App\Modules\LeaseMoveOuts\Support\LeaseMoveOutOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class LeaseMoveOutIndexQuery
{
    public function __construct(
        private LeaseMoveOutDirectoryQuery $directory,
        private LeaseMoveOutInsightsQuery $insights,
        private LeaseMoveOutRowPresenter $rows,
        private PortfolioScope $portfolios,
        private PropertyScope $properties,
        private AssetPropertyContext $assetProperties,
        private TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->directory->filters($request);
        $base = $this->directory->base($actor);
        $summary = clone $base;
        $this->directory->applyContext($summary, $filters, $actor);
        $moveOuts = $this->directory->listing(clone $base);
        $this->directory->apply($moveOuts, $filters, $actor);
        [$rootByAsset, $assetsById] = $this->assetContext($actor, $filters['portfolio_id']);

        return [
            'moveOuts' => $this->tables
                ->paginate(
                    $moveOuts,
                    $filters,
                    ['move_out_date', 'status', 'created_at'],
                    'move_out_date',
                )
                ->through(function (LeaseMoveOut $moveOut) use ($rootByAsset, $assetsById): array {
                    $assetId = $moveOut->lease?->leaseable instanceof Asset
                        ? $moveOut->lease->leaseable->id
                        : null;
                    $rootId = $assetId !== null ? ($rootByAsset[$assetId] ?? null) : null;

                    return $this->rows->present(
                        $moveOut,
                        $rootId !== null ? ($assetsById[$rootId] ?? null) : null,
                    );
                }),
            'moveOutInsights' => $this->insights->get($summary),
            'filters' => $filters,
            'counts' => $this->counts($summary, (string) $filters['queue']),
            'portfolioOptions' => $this->portfolios->options($actor),
            'propertyOptions' => $this->properties->options($actor),
            'queueOptions' => LeaseMoveOutOptions::QUEUES,
            'horizonOptions' => LeaseMoveOutOptions::HORIZONS,
        ];
    }

    /** @return Builder<LeaseMoveOut> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->directory->filters($request);
        $query = $this->directory->listing($this->directory->base($actor));
        $this->directory->apply($query, $filters, $actor);

        return $query;
    }

    /** @return array{0:array<int,int>,1:array<int,Asset>} */
    public function assetContext(User $actor, ?int $portfolioId = null): array
    {
        return $this->assetProperties->get($actor, $portfolioId);
    }

    /**
     * @param  Builder<LeaseMoveOut>  $query
     * @return list<array{label:string,value:int,filter:array<string,string>,active:bool}>
     */
    private function counts(Builder $query, string $activeQueue): array
    {
        return array_values(
            collect(['all', ...LeaseMoveOutOptions::QUEUES])
                ->map(function (string $queue) use ($query, $activeQueue): array {
                    $queueQuery = clone $query;

                    if ($queue !== 'all') {
                        $this->directory->applyQueue($queueQuery, $queue);
                    }

                    return [
                        'label' => (string) trans("app.lease_move_outs.queue_{$queue}"),
                        'value' => $queueQuery->count(),
                        'filter' => ['queue' => $queue],
                        'active' => $activeQueue === $queue,
                    ];
                })
                ->all(),
        );
    }
}
