<?php

namespace App\Modules\Tenants\Queries;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use App\Modules\Tenants\Presenters\TenantTableRowPresenter;
use App\Modules\Tenants\Support\TenantOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class TenantIndexQuery
{
    public function __construct(
        private readonly TenantDirectoryQuery $directory,
        private readonly TenantInsightsQuery $insights,
        private readonly TenantTableRowPresenter $rows,
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
        $tenants = $this->directory->listing(clone $baseQuery);
        $this->directory->apply($tenants, $filters);

        return [
            'tenants' => $this->tables->paginate($tenants, $filters, [
                'created_at',
                'status',
                'profile_type',
                'company_name',
            ])->through(fn (TenantProfile $tenant): array => $this->rows->present($tenant)),
            'filters' => $filters,
            'counts' => $this->localizedCounts($this->tables->statusCounts(
                $summaryQuery,
                TenantOptions::STATUSES,
                $filters,
            )),
            'portfolioOptions' => $this->portfolios->options($actor),
            'tenantInsights' => $this->insights->get($summaryQuery),
            'profileTypeOptions' => TenantOptions::PROFILE_TYPES,
            'statusOptions' => TenantOptions::STATUSES,
        ];
    }

    /** @return Builder<TenantProfile> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->directory->filters($request);
        $query = $this->directory->base($actor)->with(['portfolio', 'user']);
        $this->directory->apply($query, $filters);

        return $query;
    }

    /**
     * @param  array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>  $counts
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    private function localizedCounts(array $counts): array
    {
        return collect($counts)->map(function (array $count): array {
            $status = (string) data_get($count, 'filter.status', 'all');
            $count['label'] = $status === 'all'
                ? trans('app.tenants.all')
                : trans("app.status.{$status}");

            return $count;
        })->all();
    }
}
