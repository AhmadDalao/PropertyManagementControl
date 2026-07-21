<?php

namespace App\Modules\Expenses\Queries;

use App\Models\ExpenseEntry;
use App\Models\User;
use App\Modules\Expenses\Support\ExpenseAccess;
use App\Modules\Expenses\Support\ExpenseOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExpenseIndexQuery
{
    public function __construct(
        private readonly ExpenseAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $this->access->ensureManager($actor);
        $filters = $this->filters($request);
        $baseQuery = $this->portfolios->apply(ExpenseEntry::query(), $actor);
        $expenses = $this->indexQuery(clone $baseQuery);
        $this->applyFilters($expenses, $filters);
        $countScope = clone $baseQuery;
        $this->tables->exact($countScope, $filters, 'portfolio_id');
        $paginator = $this->tables->paginate($expenses, $filters, [
            'created_at',
            'incurred_on',
            'title',
            'category',
            'status',
            'amount',
        ], 'incurred_on');

        return [
            'expenses' => $paginator,
            'expenseInsights' => $this->insights($countScope),
            'filters' => $filters,
            'counts' => $this->tables->statusCounts($countScope, ExpenseOptions::STATUSES, $filters),
            'portfolioOptions' => $this->portfolios->options($actor),
            'categoryOptions' => ExpenseOptions::CATEGORIES,
            'statusOptions' => ExpenseOptions::STATUSES,
        ];
    }

    /** @return Builder<ExpenseEntry> */
    public function forExport(Request $request, User $actor): Builder
    {
        $this->access->ensureManager($actor);
        $filters = $this->filters($request);
        $query = $this->portfolios->apply(ExpenseEntry::query(), $actor)
            ->with(['asset', 'maintenanceRequest']);
        $this->applyFilters($query, $filters);

        return $query;
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $this->tables->filters($request, [
            'status' => 'all',
            'category' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);
    }

    /**
     * @param  Builder<ExpenseEntry>  $query
     * @return Builder<ExpenseEntry>
     */
    private function indexQuery(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'asset_id',
                'maintenance_request_id',
                'title',
                'description',
                'category',
                'status',
                'vendor_name',
                'amount',
                'currency',
                'incurred_on',
                'created_at',
            ])
            ->with([
                'asset:id,portfolio_id,title_en,title_ar,code',
                'maintenanceRequest:id,portfolio_id,asset_id,title,status,priority',
            ]);
    }

    /**
     * @param  Builder<ExpenseEntry>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach (['portfolio_id', 'status', 'category'] as $filter) {
            $this->tables->exact($query, $filters, $filter);
        }

        $this->tables->dateRange($query, $filters, 'incurred_on');
        $this->tables->search($query, (string) $filters['search'], [
            'title',
            'description',
            'category',
            'vendor_name',
            fn (Builder $expenses, string $search, string $like) => $expenses->orWhereHas(
                'asset',
                fn (Builder $assets) => $assets
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like),
            ),
            fn (Builder $expenses, string $search, string $like) => $expenses->orWhereHas(
                'maintenanceRequest',
                fn (Builder $requests) => $requests->where('title', 'like', $like),
            ),
        ]);
    }

    /**
     * @param  Builder<ExpenseEntry>  $baseQuery
     * @return array<string, int|float|string|null>
     */
    private function insights(Builder $baseQuery): array
    {
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();
        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("SUM(CASE WHEN status = 'void' THEN 1 ELSE 0 END) as void_count")
            ->selectRaw('SUM(CASE WHEN asset_id IS NOT NULL THEN 1 ELSE 0 END) as linked_to_assets')
            ->selectRaw('SUM(CASE WHEN maintenance_request_id IS NOT NULL THEN 1 ELSE 0 END) as linked_to_maintenance')
            ->selectRaw('SUM(CASE WHEN asset_id IS NULL AND maintenance_request_id IS NULL THEN 1 ELSE 0 END) as unlinked_count')
            ->selectRaw("COUNT(DISTINCT CASE WHEN vendor_name IS NOT NULL AND vendor_name <> '' THEN vendor_name END) as vendors")
            ->first();
        $currencyRows = (clone $baseQuery)
            ->select('currency')
            ->selectRaw("SUM(CASE WHEN status = 'posted' THEN amount ELSE 0 END) as posted_amount")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount")
            ->selectRaw("SUM(CASE WHEN status = 'void' THEN amount ELSE 0 END) as void_amount")
            ->selectRaw("SUM(CASE WHEN status = 'posted' AND category = 'maintenance' THEN amount ELSE 0 END) as maintenance_amount")
            ->selectRaw(
                "SUM(CASE WHEN status = 'posted' AND incurred_on BETWEEN ? AND ? THEN amount ELSE 0 END) as posted_this_month",
                [$monthStart, $monthEnd],
            )
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();
        $currencyCount = $currencyRows->count();
        $currencyRow = $currencyCount <= 1 ? $currencyRows->first() : null;

        return [
            'total' => (int) ($summary?->getAttribute('total') ?? 0),
            'posted_count' => (int) ($summary?->getAttribute('posted_count') ?? 0),
            'pending_count' => (int) ($summary?->getAttribute('pending_count') ?? 0),
            'void_count' => (int) ($summary?->getAttribute('void_count') ?? 0),
            'posted_amount' => $currencyRow ? (float) $currencyRow->getAttribute('posted_amount') : ($currencyCount === 0 ? 0.0 : null),
            'pending_amount' => $currencyRow ? (float) $currencyRow->getAttribute('pending_amount') : ($currencyCount === 0 ? 0.0 : null),
            'void_amount' => $currencyRow ? (float) $currencyRow->getAttribute('void_amount') : ($currencyCount === 0 ? 0.0 : null),
            'maintenance_amount' => $currencyRow ? (float) $currencyRow->getAttribute('maintenance_amount') : ($currencyCount === 0 ? 0.0 : null),
            'posted_this_month' => $currencyRow ? (float) $currencyRow->getAttribute('posted_this_month') : ($currencyCount === 0 ? 0.0 : null),
            'linked_to_assets' => (int) ($summary?->getAttribute('linked_to_assets') ?? 0),
            'linked_to_maintenance' => (int) ($summary?->getAttribute('linked_to_maintenance') ?? 0),
            'unlinked_count' => (int) ($summary?->getAttribute('unlinked_count') ?? 0),
            'vendors' => (int) ($summary?->getAttribute('vendors') ?? 0),
            'currency' => $currencyRow ? (string) $currencyRow->currency : ($currencyCount === 0 ? 'SAR' : null),
            'currency_count' => $currencyCount,
        ];
    }
}
