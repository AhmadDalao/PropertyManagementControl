<?php

namespace App\Modules\LeaseRenewals\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Assets\Support\AssetPropertyContext;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\LeaseRenewals\Presenters\LeaseRenewalDownloadPresenter;
use App\Modules\LeaseRenewals\Presenters\LeaseRenewalRowPresenter;
use App\Modules\LeaseRenewals\Support\LeaseRenewalOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final readonly class LeaseRenewalIndexQuery
{
    public function __construct(
        private LeaseRenewalDirectoryQuery $directory,
        private LeaseRenewalInsightsQuery $insights,
        private LeaseRenewalDownloadPresenter $downloads,
        private LeaseRenewalRowPresenter $rows,
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
        $leases = $this->directory->listing(clone $base);
        $this->directory->apply($leases, $filters, $actor);
        [$rootByAsset, $assetsById] = $this->assetContext($actor, $filters['portfolio_id']);

        return [
            'renewals' => $this->tables
                ->paginate(
                    $leases,
                    $filters,
                    ['ends_at', 'rent_amount', 'status', 'created_at'],
                    'ends_at',
                )
                ->through(function (Lease $lease) use ($rootByAsset, $assetsById): array {
                    $assetId = $lease->leaseable instanceof Asset
                        ? $lease->leaseable->id
                        : null;
                    $rootId = $assetId !== null ? ($rootByAsset[$assetId] ?? null) : null;

                    return $this->rows->present(
                        $lease,
                        $rootId !== null ? ($assetsById[$rootId] ?? null) : null,
                    );
                }),
            'renewalInsights' => $this->insights->get($summary),
            'filters' => $filters,
            'counts' => $this->counts($summary, (string) $filters['queue']),
            'portfolioOptions' => $this->portfolios->options($actor),
            'propertyOptions' => $this->properties->options($actor),
            'queueOptions' => LeaseRenewalOptions::QUEUES,
            'horizonOptions' => LeaseRenewalOptions::HORIZONS,
            'leaseStatusOptions' => LeaseRenewalOptions::LEASE_STATUSES,
            'downloads' => $this->downloads->present($filters),
        ];
    }

    /** @return Builder<Lease> */
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
     * @param  Builder<Lease>  $query
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    private function counts(Builder $query, string $activeQueue): array
    {
        return collect(['all', ...LeaseRenewalOptions::QUEUES])
            ->map(function (string $queue) use ($query, $activeQueue): array {
                $queueQuery = clone $query;

                if ($queue !== 'all') {
                    $this->directory->applyQueue($queueQuery, $queue);
                }

                return [
                    'label' => trans("app.lease_renewals.queue_{$queue}"),
                    'value' => $queueQuery->count(),
                    'filter' => ['queue' => $queue],
                    'active' => $activeQueue === $queue,
                ];
            })
            ->all();
    }
}
