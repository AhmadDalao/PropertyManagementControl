<?php

namespace App\Modules\Payments\Queries;

use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Presenters\PaymentTableRowPresenter;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class PaymentIndexQuery
{
    public function __construct(
        private readonly PaymentDirectoryQuery $directory,
        private readonly PaymentInsightsQuery $insights,
        private readonly PaymentTableRowPresenter $rows,
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
        $payments = $this->directory->listing(clone $baseQuery);
        $this->directory->apply($payments, $filters);

        return [
            'payments' => $this->tables->paginate($payments, $filters, [
                'created_at', 'received_on', 'reference', 'status', 'type', 'method', 'amount',
            ], 'received_on')->through(fn (Payment $payment): array => $this->rows->present($payment)),
            'paymentInsights' => $this->insights->get($summaryQuery),
            'filters' => $filters,
            'counts' => $this->localizedCounts($this->tables->statusCounts(
                $summaryQuery,
                PaymentOptions::STATUSES,
                $filters,
            )),
            'portfolioOptions' => $this->portfolios->options($actor),
            'statusOptions' => PaymentOptions::STATUSES,
            'typeOptions' => PaymentOptions::TYPES,
            'methodOptions' => PaymentOptions::METHODS,
        ];
    }

    /** @return Builder<Payment> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->directory->filters($request);
        $query = $this->directory->base($actor)->with(['lease.leaseable', 'tenantProfile.user']);
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
                ? trans('app.payments.all')
                : trans("app.status.{$status}");

            return $count;
        })->all();
    }
}
