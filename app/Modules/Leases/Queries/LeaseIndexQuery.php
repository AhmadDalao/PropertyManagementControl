<?php

namespace App\Modules\Leases\Queries;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Leases\Presenters\LeaseTableRowPresenter;
use App\Modules\Leases\Support\LeaseOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class LeaseIndexQuery
{
    public function __construct(
        private readonly LeaseDirectoryQuery $directory,
        private readonly LeaseInsightsQuery $insights,
        private readonly LeaseTableRowPresenter $rows,
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
        $leases = $this->directory->listing(clone $baseQuery);
        $this->directory->apply($leases, $filters);

        return [
            'leases' => $this->tables->paginate($leases, $filters, [
                'created_at', 'code', 'status', 'payment_frequency', 'started_at', 'ends_at',
            ], 'started_at')->through(fn (Lease $lease): array => $this->rows->present($lease)),
            'leaseInsights' => $this->insights->get($summaryQuery),
            'filters' => $filters,
            'counts' => $this->localizedCounts($this->tables->statusCounts(
                $summaryQuery,
                LeaseOptions::STATUSES,
                $filters,
            )),
            'portfolioOptions' => $this->portfolios->options($actor),
            'statusOptions' => LeaseOptions::STATUSES,
            'frequencyOptions' => LeaseOptions::PAYMENT_FREQUENCIES,
        ];
    }

    /** @return Builder<Lease> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->directory->filters($request);
        $query = $this->directory->base($actor)->with(['tenantProfile.user', 'leaseable', 'installments']);
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
                ? trans('app.leases.all')
                : trans("app.status.{$status}");

            return $count;
        })->all();
    }
}
