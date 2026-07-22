<?php

namespace App\Modules\Expenses\Queries;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Presenters\ExpenseTableRowPresenter;
use App\Modules\Expenses\Support\ExpenseOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ExpenseIndexQuery
{
    public function __construct(
        private readonly ExpenseDirectoryQuery $directory,
        private readonly ExpenseInsightsQuery $insights,
        private readonly ExpenseTableRowPresenter $rows,
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
        $expenses = $this->directory->listing(clone $baseQuery);
        $this->directory->apply($expenses, $filters);

        return [
            'expenses' => $this->tables->paginate($expenses, $filters, [
                'created_at', 'incurred_on', 'title', 'category', 'status', 'amount',
            ], 'incurred_on')->through(fn (ExpenseEntry $expense): array => $this->rows->present($expense)),
            'expenseInsights' => $this->insights->get($summaryQuery),
            'filters' => $filters,
            'counts' => $this->localizedCounts($this->tables->statusCounts(
                $summaryQuery,
                ExpenseOptions::STATUSES,
                $filters,
            )),
            'portfolioOptions' => $this->portfolios->options($actor),
            'categoryOptions' => ExpenseOptions::CATEGORIES,
            'statusOptions' => ExpenseOptions::STATUSES,
        ];
    }

    /** @return Builder<ExpenseEntry> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->directory->filters($request);
        $query = $this->directory->base($actor)->with(['asset', 'maintenanceRequest']);
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
                ? trans('app.expenses.all')
                : trans("app.status.{$status}");

            return $count;
        })->all();
    }
}
