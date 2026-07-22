<?php

namespace App\Modules\Portfolios\Queries;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioAccess;
use App\Modules\Portfolios\Support\PortfolioOptions;
use App\Modules\Shared\TableQuery;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PortfolioDirectoryQuery
{
    public function __construct(
        private readonly PortfolioAccess $access,
        private readonly UserAccess $users,
        private readonly TableQuery $tables,
    ) {}

    /** @return Builder<Portfolio> */
    public function base(User $actor): Builder
    {
        return $this->access->directoryScope(Portfolio::query(), $actor);
    }

    /** @return array<string, mixed> */
    public function filters(Request $request): array
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
    public function listing(Builder $query, User $actor): Builder
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
    public function applyFilters(Builder $query, array $filters): void
    {
        $this->tables->exact($query, $filters, 'status');
        $this->applySearch($query, (string) $filters['search']);
    }

    /**
     * @param  Builder<Portfolio>  $query
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function statusCounts(Builder $query, array $filters): array
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

    /** @return array<int, Portfolio> */
    public function search(User $actor, string $search, int $limit = 5): array
    {
        $query = $this->base($actor)
            ->select(['id', 'name_en', 'name_ar', 'code', 'status']);
        $this->applySearch($query, $search);

        return $query->limit($limit)->get()->all();
    }

    public function exactCode(User $actor, string $code): ?Portfolio
    {
        return $this->base($actor)->where('code', $code)->first();
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
}
