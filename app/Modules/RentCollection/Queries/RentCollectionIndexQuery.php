<?php

namespace App\Modules\RentCollection\Queries;

use App\Models\Asset;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Assets\Support\AssetPropertyContext;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\RentCollection\Presenters\RentCollectionRowPresenter;
use App\Modules\RentCollection\Support\CollectionFollowUpOptions;
use App\Modules\RentCollection\Support\RentCollectionOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class RentCollectionIndexQuery
{
    public function __construct(
        private RentCollectionDirectoryQuery $directory,
        private RentCollectionInsightsQuery $insights,
        private RentCollectionRowPresenter $rows,
        private PortfolioScope $portfolios,
        private PropertyScope $properties,
        private AssetPropertyContext $assetProperties,
        private TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->directory->filters($request);
        $baseQuery = $this->directory->base($actor);
        $summaryQuery = clone $baseQuery;
        $this->directory->applyScope($summaryQuery, $filters, $actor);
        $installments = $this->directory->listing(clone $baseQuery);
        $this->directory->apply($installments, $filters, $actor);
        [$rootByAsset, $assetsById] = $this->assetContext($actor, $filters['portfolio_id']);

        return [
            'installments' => $this->tables
                ->paginate(
                    $installments,
                    $filters,
                    ['due_date', 'amount_due', 'amount_paid', 'status', 'created_at'],
                    'due_date',
                )
                ->through(function (LeaseInstallment $installment) use ($rootByAsset, $assetsById): array {
                    $assetId = $installment->lease?->leaseable instanceof Asset
                        ? $installment->lease->leaseable->id
                        : null;
                    $rootId = $assetId !== null ? ($rootByAsset[$assetId] ?? null) : null;

                    return $this->rows->present(
                        $installment,
                        $rootId !== null ? ($assetsById[$rootId] ?? null) : null,
                    );
                }),
            'collectionInsights' => $this->insights->get($summaryQuery),
            'filters' => $filters,
            'counts' => $this->counts($summaryQuery, (string) $filters['status']),
            'portfolioOptions' => $this->portfolios->options($actor),
            'propertyOptions' => $this->properties->options($actor),
            'statusOptions' => RentCollectionOptions::STATUSES,
            'lineTypeOptions' => RentCollectionOptions::LINE_TYPES,
            'followUpOptions' => CollectionFollowUpOptions::STATES,
        ];
    }

    /** @return Builder<LeaseInstallment> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->directory->filters($request);
        $query = $this->directory->listing($this->directory->base($actor));
        $this->directory->apply($query, $filters, $actor);

        return $query;
    }

    /**
     * @return array{
     *     0:array<int, int>,
     *     1:array<int, Asset>
     * }
     */
    public function assetContext(User $actor, ?int $portfolioId = null): array
    {
        return $this->assetProperties->get($actor, $portfolioId);
    }

    /**
     * @param  Builder<LeaseInstallment>  $query
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    private function counts(Builder $query, string $activeStatus): array
    {
        $statuses = ['all', ...RentCollectionOptions::STATUSES];

        return collect($statuses)->map(function (string $status) use ($query, $activeStatus): array {
            $statusQuery = clone $query;

            if ($status !== 'all') {
                $this->directory->applyStatus($statusQuery, $status);
            }

            return [
                'label' => trans("app.rent_collection.status_{$status}"),
                'value' => $statusQuery->count(),
                'filter' => ['status' => $status],
                'active' => $activeStatus === $status,
            ];
        })->all();
    }
}
