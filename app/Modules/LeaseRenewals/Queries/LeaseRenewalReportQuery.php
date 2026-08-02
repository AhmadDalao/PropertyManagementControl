<?php

namespace App\Modules\LeaseRenewals\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\LeaseRenewals\Presenters\LeaseRenewalRowPresenter;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Http\Request;

final readonly class LeaseRenewalReportQuery
{
    public function __construct(
        private LeaseRenewalDirectoryQuery $directory,
        private LeaseRenewalIndexQuery $index,
        private LeaseRenewalRowPresenter $rows,
        private PortfolioScope $portfolios,
        private PropertyScope $properties,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->directory->filters($request);
        $query = $this->directory->listing($this->directory->base($actor));
        $this->directory->apply($query, $filters, $actor);
        [$rootByAsset, $assetsById] = $this->index->assetContext(
            $actor,
            $filters['portfolio_id'],
        );
        $records = $query
            ->orderBy('ends_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Lease $lease): array => $this->row(
                $lease,
                $rootByAsset,
                $assetsById,
            ))
            ->values();

        return [
            'filters' => $filters,
            'records' => $records->all(),
            'summary' => [
                'total' => $records->count(),
                'attention' => $records->where('renewal_state', 'attention')->count(),
                'upcoming' => $records->where('renewal_state', 'upcoming')->count(),
                'prepared' => $records->where('renewal_state', 'prepared')->count(),
                'expired' => $records->where('renewal_state', 'expired')->count(),
            ],
            'currencyPositions' => $records
                ->groupBy('currency')
                ->map(fn ($currencyRecords, string $currency): array => [
                    'currency' => $currency,
                    'leases' => $currencyRecords->count(),
                    'attention' => $currencyRecords->where('renewal_state', 'attention')->count(),
                    'prepared' => $currencyRecords->where('renewal_state', 'prepared')->count(),
                    'expired' => $currencyRecords->where('renewal_state', 'expired')->count(),
                    'outstanding' => (float) $currencyRecords->sum('outstanding_amount'),
                ])
                ->sortKeys()
                ->values()
                ->all(),
            'scope' => $this->scope($actor, $filters),
        ];
    }

    /**
     * @param  array<int, int>  $rootByAsset
     * @param  array<int, Asset>  $assetsById
     * @return array<string, mixed>
     */
    private function row(
        Lease $lease,
        array $rootByAsset,
        array $assetsById,
    ): array {
        $assetId = $lease->leaseable instanceof Asset
            ? $lease->leaseable->id
            : null;
        $rootId = $assetId !== null ? ($rootByAsset[$assetId] ?? null) : null;

        return $this->rows->present(
            $lease,
            $rootId !== null ? ($assetsById[$rootId] ?? null) : null,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{label:string,value:string}>
     */
    private function scope(User $actor, array $filters): array
    {
        $portfolioOptions = $this->portfolios->options($actor);
        $propertyOptions = $this->properties->options($actor);
        $portfolio = collect($portfolioOptions)->firstWhere('id', $filters['portfolio_id']);
        $property = collect($propertyOptions)->firstWhere('id', $filters['property_id']);
        $status = (string) $filters['lease_status'];
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
                'label' => trans('app.lease_renewals.queue'),
                'value' => trans("app.lease_renewals.queue_{$filters['queue']}"),
            ],
            [
                'label' => trans('app.lease_renewals.horizon'),
                'value' => trans("app.lease_renewals.horizon_{$filters['horizon']}"),
            ],
            [
                'label' => trans('app.lease_renewals.lease_status'),
                'value' => $status === 'all'
                    ? trans('app.lease_renewals.status_all')
                    : trans("app.status.{$status}"),
            ],
        ];

        if ($filters['search'] !== '') {
            $scope[] = [
                'label' => trans('app.actions.search'),
                'value' => (string) $filters['search'],
            ];
        }

        return $scope;
    }
}
