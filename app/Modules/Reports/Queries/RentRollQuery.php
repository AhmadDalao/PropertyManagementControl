<?php

namespace App\Modules\Reports\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Assets\Support\AssetPropertyContext;
use App\Modules\Reports\Presenters\RentRollFinancialPresenter;
use App\Modules\Reports\Presenters\RentRollRowPresenter;
use App\Modules\Reports\Presenters\ReportLibraryScopePresenter;
use App\Modules\Reports\Support\RentRollOptions;
use App\Modules\Reports\Support\ReportAccess;
use App\Modules\Reports\Support\ReportPropertyScope;
use App\Modules\Reports\Support\ReportQueryScope;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final readonly class RentRollQuery
{
    public function __construct(
        private ReportAccess $access,
        private ReportQueryScope $scope,
        private ReportPropertyScope $properties,
        private PortfolioScope $portfolios,
        private AssetPropertyContext $assetContext,
        private TableQuery $tables,
        private RentRollRowPresenter $rows,
        private RentRollFinancialPresenter $financials,
        private ReportLibraryScopePresenter $scopePresenter,
    ) {}

    /**
     * @param  array{
     *     search:string,state:string,portfolio_id:int|null,property_id:int|null,
     *     per_page:int,page:int,sort:string,direction:string
     * }  $filters
     * @return array<string, mixed>
     */
    public function present(User $actor, array $filters): array
    {
        $base = $this->base($actor, $filters);
        $this->applySearch($base, $filters['search']);
        $counts = $this->counts($base, $filters['state']);
        $listing = clone $base;
        $this->applyState($listing, $filters['state']);
        $matchingIds = (clone $listing)
            ->pluck('assets.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        [$rootByAsset, $assetsById] = $this->assetContext->get(
            $actor,
            $filters['portfolio_id'],
        );
        $portfolioOptions = $this->portfolios->options($actor);
        $propertyOptions = $this->properties->options($actor);
        $records = $this->tables->paginate(
            $this->listing($listing),
            $filters,
            ['title_en', 'title_ar', 'code', 'asset_type'],
            app()->isLocale('ar') ? 'title_ar' : 'title_en',
        );
        $this->attachActiveLeases($records->getCollection());

        return [
            'records' => $records
                ->through(
                    fn (Asset $asset): array => $this->rows->present(
                        $asset,
                        $rootByAsset,
                        $assetsById,
                    ),
                ),
            'filters' => $filters,
            'counts' => $counts,
            'insights' => [
                'matching' => count($matchingIds),
                'occupied' => $this->countFor($base, 'occupied'),
                'vacant' => $this->countFor($base, 'vacant'),
                'attention' => $this->attentionCount($base),
            ],
            'currencyPositions' => $this->financials->present(
                $this->activeLeases($matchingIds),
            ),
            'scope' => $this->scopePresenter->present(
                $actor,
                [
                    'period' => 'custom',
                    'date_from' => today()->toDateString(),
                    'date_to' => today()->toDateString(),
                    'portfolio_id' => $filters['portfolio_id'],
                    'property_id' => $filters['property_id'],
                ],
                $portfolioOptions,
                $propertyOptions,
            )['current'],
            'portfolioOptions' => $portfolioOptions,
            'propertyOptions' => $propertyOptions,
            'stateOptions' => ['all', ...RentRollOptions::STATES],
            'downloads' => $this->downloads($filters),
        ];
    }

    /**
     * @param  array{
     *     search:string,state:string,portfolio_id:int|null,property_id:int|null,
     *     per_page:int,page:int,sort:string,direction:string
     * }  $filters
     * @return array<string, mixed>
     */
    public function export(User $actor, array $filters): array
    {
        $query = $this->base($actor, $filters);
        $this->applySearch($query, $filters['search']);
        $this->applyState($query, $filters['state']);
        $assetIds = (clone $query)
            ->pluck('assets.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        [$rootByAsset, $assetsById] = $this->assetContext->get(
            $actor,
            $filters['portfolio_id'],
        );
        $portfolioOptions = $this->portfolios->options($actor);
        $propertyOptions = $this->properties->options($actor);
        $records = $this->listing($query)
            ->orderBy(app()->isLocale('ar') ? 'title_ar' : 'title_en')
            ->orderBy('id')
            ->get();
        $this->attachActiveLeases($records);

        return [
            'filters' => $filters,
            'scope' => $this->scopePresenter->present(
                $actor,
                [
                    'period' => 'custom',
                    'date_from' => today()->toDateString(),
                    'date_to' => today()->toDateString(),
                    'portfolio_id' => $filters['portfolio_id'],
                    'property_id' => $filters['property_id'],
                ],
                $portfolioOptions,
                $propertyOptions,
            )['current'],
            'records' => $records
                ->map(
                    fn (Asset $asset): array => $this->rows->present(
                        $asset,
                        $rootByAsset,
                        $assetsById,
                    ),
                )
                ->all(),
            'currencyPositions' => $this->financials->present(
                $this->activeLeases($assetIds),
            ),
        ];
    }

    /**
     * @param  array{portfolio_id:int|null,property_id:int|null}  $filters
     * @return Builder<Asset>
     */
    private function base(User $actor, array $filters): Builder
    {
        $this->access->ensurePortfolioFilter($actor, $filters['portfolio_id']);
        $assetIds = $this->properties->assetIds(
            $actor,
            $filters['portfolio_id'],
            $filters['property_id'],
        );

        return $this->scope
            ->apply(Asset::query(), $actor, $filters['portfolio_id'])
            ->where('status', 'active')
            ->where('rentable', true)
            ->when(
                $assetIds !== null,
                fn (Builder $query) => $query->whereIn('assets.id', $assetIds),
            );
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    private function listing(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'parent_id',
                'asset_type',
                'usage_type',
                'title_en',
                'title_ar',
                'code',
                'occupancy_status',
            ])
            ->with('portfolio:id,name_en,name_ar');
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function activeLease(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereDate('started_at', '<=', today())
            ->whereDate('ends_at', '>=', today());
    }

    /**
     * @param  Builder<Lease>  $query
     * @return Builder<Lease>
     */
    private function leaseFinancials(Builder $query): Builder
    {
        return $query
            ->withSum('installments as installments_total_due', 'amount_due')
            ->withSum('installments as installments_total_paid', 'amount_paid')
            ->withSum([
                'installments as installments_overdue_due' => fn (Builder $installments) => $installments
                    ->whereDate('due_date', '<', today()),
            ], 'amount_due')
            ->withSum([
                'installments as installments_overdue_paid' => fn (Builder $installments) => $installments
                    ->whereDate('due_date', '<', today()),
            ], 'amount_paid');
    }

    /**
     * @param  array<int, int|string>  $assetIds
     * @return EloquentCollection<int, Lease>
     */
    private function activeLeases(array $assetIds): EloquentCollection
    {
        if ($assetIds === []) {
            return new EloquentCollection;
        }

        return $this->leaseFinancials(
            $this->activeLease(
                Lease::query()
                    ->whereIn('leaseable_type', $this->properties->leaseableTypes())
                    ->whereIn('leaseable_id', $assetIds)
                    ->select([
                        'id',
                        'portfolio_id',
                        'tenant_profile_id',
                        'leaseable_type',
                        'leaseable_id',
                        'code',
                        'status',
                        'payment_frequency',
                        'started_at',
                        'ends_at',
                        'rent_amount',
                        'deposit_amount',
                        'currency',
                    ]),
            ),
        )
            ->with([
                'tenantProfile:id,user_id,company_name',
                'tenantProfile.user:id,name',
            ])
            ->orderByDesc('started_at')
            ->get();
    }

    /** @param Collection<int, Asset> $assets */
    private function attachActiveLeases(Collection $assets): void
    {
        /** @var array<int, Lease> $leasesByAsset */
        $leasesByAsset = [];

        $assetIds = $assets
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        foreach ($this->activeLeases($assetIds) as $lease) {
            if (! isset($leasesByAsset[$lease->leaseable_id])) {
                $leasesByAsset[$lease->leaseable_id] = $lease;
            }
        }

        foreach ($assets as $asset) {
            $lease = $leasesByAsset[$asset->id] ?? null;
            $asset->setRelation(
                'leases',
                new EloquentCollection($lease ? [$lease] : []),
            );
        }
    }

    /** @param Builder<Asset> $query */
    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = "%{$search}%";
        $query->where(function (Builder $assets) use ($like): void {
            $assets
                ->where('title_en', 'like', $like)
                ->orWhere('title_ar', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('address', 'like', $like)
                ->orWhere('address_ar', 'like', $like)
                ->orWhereHas('portfolio', fn (Builder $portfolios) => $portfolios
                    ->where('name_en', 'like', $like)
                    ->orWhere('name_ar', 'like', $like)
                    ->orWhere('code', 'like', $like))
                ->orWhereIn(
                    'assets.id',
                    $this->activeLeaseIds()
                        ->where(function (Builder $active) use ($like): void {
                            $active
                                ->where('code', 'like', $like)
                                ->orWhereHas('tenantProfile', fn (Builder $tenants) => $tenants
                                    ->where('company_name', 'like', $like)
                                    ->orWhereHas('user', fn (Builder $users) => $users
                                        ->where('name', 'like', $like)
                                        ->orWhere('email', 'like', $like)));
                        }),
                );
        });
    }

    /** @param Builder<Asset> $query */
    private function applyState(Builder $query, string $state): void
    {
        match ($state) {
            'occupied' => $this->occupied($query),
            'vacant' => $this->vacant($query),
            'arrears' => $this->arrears($query),
            'expiring' => $this->expiring($query),
            default => null,
        };
    }

    /** @param Builder<Asset> $query */
    private function occupied(Builder $query): void
    {
        $query->whereIn('assets.id', $this->activeLeaseIds());
    }

    /** @param Builder<Asset> $query */
    private function vacant(Builder $query): void
    {
        $query->whereNotIn('assets.id', $this->activeLeaseIds());
    }

    /** @param Builder<Asset> $query */
    private function arrears(Builder $query): void
    {
        $query->whereIn(
            'assets.id',
            $this->activeLeaseIds()
                ->whereHas('installments', fn (Builder $installments) => $installments
                    ->whereDate('due_date', '<', today())
                    ->whereColumn('amount_paid', '<', 'amount_due')),
        );
    }

    /** @param Builder<Asset> $query */
    private function expiring(Builder $query): void
    {
        $query->whereIn(
            'assets.id',
            $this->activeLeaseIds()
                ->whereDate('ends_at', '<=', today()->addDays(90)),
        );
    }

    /**
     * @param  Builder<Asset>  $query
     * @return list<array{label:string,value:int,filter:array<string,string>,active:bool}>
     */
    private function counts(Builder $query, string $activeState): array
    {
        return array_values(collect(['all', ...RentRollOptions::STATES])
            ->map(fn (string $state): array => [
                'label' => (string) trans("app.reports.rent_roll_state_{$state}"),
                'value' => $state === 'all'
                    ? (clone $query)->count()
                    : $this->countFor($query, $state),
                'filter' => ['state' => $state],
                'active' => $activeState === $state,
            ])
            ->all());
    }

    /** @param Builder<Asset> $query */
    private function countFor(Builder $query, string $state): int
    {
        $count = clone $query;
        $this->applyState($count, $state);

        return $count->count();
    }

    /** @param Builder<Asset> $query */
    private function attentionCount(Builder $query): int
    {
        return (clone $query)
            ->where(function (Builder $attention): void {
                $attention
                    ->whereIn(
                        'assets.id',
                        $this->activeLeaseIds()
                            ->whereHas('installments', fn (Builder $installments) => $installments
                                ->whereDate('due_date', '<', today())
                                ->whereColumn('amount_paid', '<', 'amount_due')),
                    )
                    ->orWhereIn(
                        'assets.id',
                        $this->activeLeaseIds()
                            ->whereDate('ends_at', '<=', today()->addDays(90)),
                    );
            })
            ->count();
    }

    /** @return Builder<Lease> */
    private function activeLeaseIds(): Builder
    {
        return $this->activeLease(
            Lease::query()
                ->whereIn('leaseable_type', $this->properties->leaseableTypes()),
        )->select('leaseable_id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{pdf:string,docx:string,xlsx:string}
     */
    private function downloads(array $filters): array
    {
        $query = array_filter([
            'search' => $filters['search'],
            'state' => $filters['state'] !== 'all' ? $filters['state'] : null,
            'portfolio_id' => $filters['portfolio_id'],
            'property_id' => $filters['property_id'],
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return [
            'pdf' => route('reports.rent-roll.pdf', $query, false),
            'docx' => route('reports.rent-roll.word', $query, false),
            'xlsx' => route('reports.rent-roll.workbook', $query, false),
        ];
    }
}
