<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Presenters\AssetTableRowPresenter;
use App\Modules\Assets\Support\AssetOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AssetIndexQuery
{
    public function __construct(
        private readonly AssetDirectoryQuery $directory,
        private readonly AssetInsightsQuery $insights,
        private readonly AssetTableRowPresenter $rows,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->directory->filters($request);
        $baseQuery = $this->directory->base($actor);
        $summaryQuery = clone $baseQuery;
        $this->directory->applyPortfolio($summaryQuery, $filters);
        $assets = $this->directory->listing(clone $baseQuery);
        $this->directory->apply($assets, $filters);

        return [
            'assets' => $this->tables->paginate($assets, $filters, [
                'created_at',
                'title_en',
                'code',
                'asset_type',
                'usage_type',
                'status',
                'occupancy_status',
                'valuation_amount',
            ])->through(fn (Asset $asset): array => $this->rows->present($asset)),
            'filters' => $filters,
            'counts' => $this->localizedCounts($this->tables->statusCounts(
                $summaryQuery,
                AssetOptions::STATUSES,
                $filters,
            )),
            'insights' => $this->insights->get($summaryQuery),
            'portfolioOptions' => $this->portfolios->options($actor),
        ];
    }

    /** @return Builder<Asset> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->directory->filters($request);
        $assets = $this->directory->base($actor)->with(['portfolio', 'parent']);
        $this->directory->apply($assets, $filters);

        return $assets;
    }

    /**
     * @param  array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>  $counts
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    private function localizedCounts(array $counts): array
    {
        return collect($counts)->map(function (array $count): array {
            $status = $count['filter']['status'] ?? 'all';
            $count['label'] = $status === 'all'
                ? trans('app.assets.all')
                : trans("app.status.{$status}");

            return $count;
        })->all();
    }
}
