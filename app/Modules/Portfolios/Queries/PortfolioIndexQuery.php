<?php

namespace App\Modules\Portfolios\Queries;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioAccess;
use App\Modules\Portfolios\Support\PortfolioOptions;
use App\Modules\Shared\TableQuery;
use App\Modules\Users\Support\UserAccess;
use App\Support\PortfolioModules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PortfolioIndexQuery
{
    public function __construct(
        private readonly PortfolioAccess $access,
        private readonly UserAccess $users,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->filters($request);
        $baseQuery = $this->access->directoryScope(Portfolio::query(), $actor);
        $portfolios = $this->indexQuery(clone $baseQuery, $actor);
        $this->applyFilters($portfolios, $filters);

        return [
            'portfolios' => $this->tables->paginate($portfolios, $filters, [
                'created_at',
                'name_en',
                'code',
                'status',
                'city',
            ]),
            'portfolioInsights' => $this->insights(clone $baseQuery, $actor),
            'filters' => $filters,
            'counts' => $this->localizedStatusCounts(clone $baseQuery, $filters),
            'canCreate' => $this->access->canCreate($actor),
            'canUpdate' => $actor->hasAnyRole(['superadmin', 'owner']),
            'canArchive' => $this->access->canArchive($actor),
            'moduleDefinitions' => PortfolioModules::definitions(),
            'statusOptions' => PortfolioOptions::STATUSES,
        ];
    }

    /** @return Builder<Portfolio> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->filters($request);
        $query = $this->indexQuery(
            $this->access->directoryScope(Portfolio::query(), $actor),
            $actor,
        );
        $this->applyFilters($query, $filters);

        return $query;
    }

    /** @return array<int, Portfolio> */
    public function search(User $actor, string $search, int $limit = 5): array
    {
        $query = $this->access->directoryScope(Portfolio::query(), $actor)
            ->select(['id', 'name_en', 'name_ar', 'code', 'status']);
        $this->applySearch($query, $search);

        return $query->limit($limit)->get()->all();
    }

    public function exactCode(User $actor, string $code): ?Portfolio
    {
        return $this->access->directoryScope(Portfolio::query(), $actor)
            ->where('code', $code)
            ->first();
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        $filters = $this->tables->filters($request, ['status' => 'all']);

        if (! in_array($filters['status'], ['all', ...PortfolioOptions::STATUSES], true)) {
            $filters['status'] = 'all';
        }

        return $filters;
    }

    /**
     * @param  Builder<Portfolio>  $query
     * @return Builder<Portfolio>
     */
    private function indexQuery(Builder $query, User $actor): Builder
    {
        return $query
            ->select([
                'id',
                'showcase_dataset_id',
                'owner_user_id',
                'name_en',
                'name_ar',
                'code',
                'status',
                'contact_email',
                'contact_phone',
                'city',
                'country',
                'default_currency',
                'module_settings',
                'created_at',
            ])
            ->with('owner:id,name,status')
            ->withCount([
                'assets',
                'users' => fn (Builder $users) => $this->users->directoryScope($users, $actor),
                'leases',
                'leases as active_leases_count' => fn (Builder $leases) => $leases->where('status', 'active'),
                'maintenanceRequests as open_maintenance_count' => fn (Builder $requests) => $requests->whereIn('status', ['open', 'in_progress']),
            ])
            ->withSum(
                ['assets as valuation_total' => fn (Builder $assets) => $assets->where('status', '!=', 'archived')],
                'valuation_amount',
            )
            ->withSum(
                ['payments as posted_revenue_total' => fn (Builder $payments) => $payments->where('status', 'posted')],
                'amount',
            )
            ->withSum(
                ['expenseEntries as posted_expense_total' => fn (Builder $expenses) => $expenses->where('status', 'posted')],
                'amount',
            );
    }

    /**
     * @param  Builder<Portfolio>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $this->tables->exact($query, $filters, 'status');
        $this->applySearch($query, (string) $filters['search']);
    }

    /** @param Builder<Portfolio> $query */
    private function applySearch(Builder $query, string $search): void
    {
        $this->tables->search($query, $search, [
            'name_en',
            'name_ar',
            'code',
            'contact_email',
            'contact_phone',
            'city',
            'country',
            'address',
            'address_ar',
        ]);
    }

    /**
     * @param  Builder<Portfolio>  $query
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function localizedStatusCounts(Builder $query, array $filters): array
    {
        return collect($this->tables->statusCounts($query, PortfolioOptions::STATUSES, $filters))
            ->map(function (array $count): array {
                $status = (string) data_get($count, 'filter.status', 'all');
                $count['label'] = $status === 'all'
                    ? trans('app.portfolios.all')
                    : trans("app.status.{$status}");

                return $count;
            })
            ->all();
    }

    /**
     * @param  Builder<Portfolio>  $baseQuery
     * @return array<string, int|float|string|null>
     */
    private function insights(Builder $baseQuery, User $actor): array
    {
        $portfolioSummary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count")
            ->selectRaw("SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count")
            ->first();
        $portfolioIds = (clone $baseQuery)->select('id');
        $assetSummary = Asset::query()
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status != 'archived' THEN valuation_amount ELSE 0 END) as valuation_total")
            ->first();
        $leaseSummary = Lease::query()
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->first();
        $openMaintenance = MaintenanceRequest::query()
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();
        $revenue = Payment::query()
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->where('status', 'posted')
            ->sum('amount');
        $expenses = ExpenseEntry::query()
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->where('status', 'posted')
            ->sum('amount');
        $visibleUsers = $this->users->directoryScope(User::query(), $actor)
            ->whereIn('portfolio_id', clone $portfolioIds)
            ->count();
        $currencies = (clone $baseQuery)
            ->whereNotNull('default_currency')
            ->distinct()
            ->orderBy('default_currency')
            ->pluck('default_currency');
        $currencyCount = $currencies->count();
        $singleCurrency = $currencyCount <= 1;
        $valuation = (float) ($assetSummary?->getAttribute('valuation_total') ?? 0);
        $revenueTotal = (float) $revenue;
        $expenseTotal = (float) $expenses;

        return [
            'total' => (int) ($portfolioSummary?->getAttribute('total') ?? 0),
            'active' => (int) ($portfolioSummary?->getAttribute('active_count') ?? 0),
            'inactive' => (int) ($portfolioSummary?->getAttribute('inactive_count') ?? 0),
            'archived' => (int) ($portfolioSummary?->getAttribute('archived_count') ?? 0),
            'assets' => (int) ($assetSummary?->getAttribute('total') ?? 0),
            'users' => $visibleUsers,
            'leases' => (int) ($leaseSummary?->getAttribute('total') ?? 0),
            'active_leases' => (int) ($leaseSummary?->getAttribute('active_count') ?? 0),
            'open_maintenance' => $openMaintenance,
            'valuation_total' => $singleCurrency ? $valuation : null,
            'posted_revenue_total' => $singleCurrency ? $revenueTotal : null,
            'posted_expense_total' => $singleCurrency ? $expenseTotal : null,
            'net_total' => $singleCurrency ? $revenueTotal - $expenseTotal : null,
            'currency' => $currencies->first() ?: 'SAR',
            'currency_count' => $currencyCount,
        ];
    }
}
