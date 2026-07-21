<?php

namespace App\Modules\Users\Queries;

use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use App\Modules\Users\Support\UserAccess;
use App\Modules\Users\Support\UserOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserIndexQuery
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->filters($request, $actor);
        $baseQuery = $this->access->directoryScope(User::query(), $actor);
        $users = $this->indexQuery(clone $baseQuery);
        $this->applyFilters($users, $filters);

        $metricScope = clone $baseQuery;
        $this->tables->exact($metricScope, $filters, 'portfolio_id');
        $countScope = clone $metricScope;
        $this->applyRoleFilter($countScope, (string) $filters['role']);

        return [
            'users' => $this->tables->paginate($users, $filters, [
                'created_at',
                'name',
                'email',
                'status',
                'last_login_at',
            ]),
            'filters' => $filters,
            'counts' => $this->localizedStatusCounts($countScope, $filters),
            'portfolioOptions' => $this->portfolios->options($actor),
            'roleOptions' => UserOptions::visibleRoles($actor),
            'statusOptions' => UserOptions::STATUSES,
            'userInsights' => $this->insights($metricScope),
        ];
    }

    /** @return Builder<User> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->filters($request, $actor);
        $query = $this->access->directoryScope(User::query(), $actor)
            ->with(['portfolio', 'roles']);
        $this->applyFilters($query, $filters);

        return $query;
    }

    /** @return array<string, mixed> */
    private function filters(Request $request, User $actor): array
    {
        $filters = $this->tables->filters($request, [
            'status' => 'all',
            'role' => 'all',
        ]);

        if (! in_array($filters['status'], ['all', ...UserOptions::STATUSES], true)) {
            $filters['status'] = 'all';
        }

        if (! in_array($filters['role'], ['all', ...UserOptions::visibleRoles($actor)], true)) {
            $filters['role'] = 'all';
        }

        return $filters;
    }

    /** @return Builder<User> */
    private function indexQuery(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'showcase_dataset_id',
                'name',
                'email',
                'phone',
                'preferred_locale',
                'status',
                'force_password_reset',
                'last_login_at',
                'created_at',
            ])
            ->with([
                'portfolio:id,showcase_dataset_id,name_en,name_ar,code,status',
                'roles:id,name',
                'tenantProfile:id,portfolio_id,user_id,profile_type,status',
            ])
            ->withCount([
                'portfoliosOwned',
                'assignedMaintenanceRequests as open_assignments_count' => fn (Builder $requests) => $requests->whereIn('status', ['open', 'in_progress']),
            ]);
    }

    /**
     * @param  Builder<User>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');
        $this->tables->exact($query, $filters, 'status');
        $this->applyRoleFilter($query, (string) $filters['role']);
        $this->tables->search($query, (string) $filters['search'], [
            'name',
            'email',
            'phone',
            fn (Builder $users, string $search, string $like) => $users->orWhereHas(
                'roles',
                fn (Builder $roles) => $roles->where('name', 'like', $like),
            ),
            fn (Builder $users, string $search, string $like) => $users->orWhereHas(
                'portfolio',
                fn (Builder $portfolios) => $portfolios
                    ->where('name_en', 'like', $like)
                    ->orWhere('name_ar', 'like', $like)
                    ->orWhere('code', 'like', $like),
            ),
        ]);
    }

    /** @param Builder<User> $query */
    private function applyRoleFilter(Builder $query, string $role): void
    {
        if ($role !== 'all') {
            $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', $role));
        }
    }

    /**
     * @param  Builder<User>  $query
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function localizedStatusCounts(Builder $query, array $filters): array
    {
        return collect($this->tables->statusCounts($query, UserOptions::STATUSES, $filters))
            ->map(function (array $count): array {
                $status = (string) data_get($count, 'filter.status', 'all');
                $count['label'] = $status === 'all'
                    ? trans('app.users.all')
                    : trans("app.status.{$status}");

                return $count;
            })
            ->all();
    }

    /**
     * @param  Builder<User>  $baseQuery
     * @return array<string, int>
     */
    private function insights(Builder $baseQuery): array
    {
        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_count")
            ->selectRaw('SUM(CASE WHEN force_password_reset = 1 THEN 1 ELSE 0 END) as temporary_passwords')
            ->first();

        return [
            'total' => (int) ($summary?->getAttribute('total') ?? 0),
            'active' => (int) ($summary?->getAttribute('active_count') ?? 0),
            'suspended' => (int) ($summary?->getAttribute('suspended_count') ?? 0),
            'temporary_passwords' => (int) ($summary?->getAttribute('temporary_passwords') ?? 0),
            'tenants_without_profile' => (clone $baseQuery)
                ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'tenant'))
                ->whereDoesntHave('tenantProfile')
                ->count(),
        ];
    }
}
