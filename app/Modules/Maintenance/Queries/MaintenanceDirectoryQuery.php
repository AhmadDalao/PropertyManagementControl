<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MaintenanceDirectoryQuery
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        return $this->tables->filters($request, [
            'status' => 'all',
            'category' => 'all',
            'priority' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);
    }

    /** @return Builder<MaintenanceRequest> */
    public function tenantBase(User $actor): Builder
    {
        return MaintenanceRequest::query()->whereHas(
            'tenantProfile',
            fn (Builder $tenants) => $tenants->where('user_id', $actor->id),
        );
    }

    /** @return Builder<MaintenanceRequest> */
    public function managerBase(User $actor): Builder
    {
        $this->access->ensureManager($actor);

        return $this->portfolios->apply(MaintenanceRequest::query(), $actor);
    }

    /**
     * @param  Builder<MaintenanceRequest>  $query
     * @return Builder<MaintenanceRequest>
     */
    public function listing(Builder $query, bool $includeFinancials): Builder
    {
        $query->select([
            'id',
            'portfolio_id',
            'asset_id',
            'tenant_profile_id',
            'assigned_to_user_id',
            'category',
            'priority',
            'status',
            'title',
            'requested_at',
            'due_at',
            'resolved_at',
            'created_at',
        ])->with([
            'asset:id,portfolio_id,title_en,title_ar,code',
            'tenantProfile:id,portfolio_id,user_id',
            'tenantProfile.user:id,name',
            'assignedTo:id,name',
        ]);

        if ($includeFinancials) {
            $query
                ->withCount('expenses')
                ->withSum(
                    ['expenses as posted_expense_total' => fn (Builder $expenses) => $expenses->where('status', 'posted')],
                    'amount',
                );
        }

        return $query;
    }

    /**
     * @param  Builder<MaintenanceRequest>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyTenantFilters(Builder $query, array $filters): void
    {
        $this->applyCommonFilters($query, $filters);
        $this->tables->search($query, (string) $filters['search'], [
            'title',
            'description',
            'category',
            fn (Builder $requests, string $search, string $like) => $requests->orWhereHas(
                'asset',
                fn (Builder $assets) => $assets
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like),
            ),
        ]);
    }

    /**
     * @param  Builder<MaintenanceRequest>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyManagerFilters(Builder $query, array $filters): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');
        $this->applyCommonFilters($query, $filters);
        $this->tables->search($query, (string) $filters['search'], [
            'title',
            'description',
            'category',
            'internal_notes',
            fn (Builder $requests, string $search, string $like) => $requests->orWhereHas(
                'asset',
                fn (Builder $assets) => $assets
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like),
            ),
            fn (Builder $requests, string $search, string $like) => $requests->orWhereHas(
                'tenantProfile.user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like),
            ),
            fn (Builder $requests, string $search, string $like) => $requests->orWhereHas(
                'assignedTo',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like),
            ),
        ]);
    }

    /**
     * @param  Builder<MaintenanceRequest>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyCommonFilters(Builder $query, array $filters): void
    {
        foreach (['status', 'category', 'priority'] as $filter) {
            $this->tables->exact($query, $filters, $filter);
        }

        $this->tables->dateRange($query, $filters, 'created_at');
    }
}
