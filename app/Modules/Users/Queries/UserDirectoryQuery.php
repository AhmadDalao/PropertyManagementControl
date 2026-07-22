<?php

namespace App\Modules\Users\Queries;

use App\Models\User;
use App\Modules\Shared\TableQuery;
use App\Modules\Users\Support\UserAccess;
use App\Modules\Users\Support\UserOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class UserDirectoryQuery
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function filters(Request $request, User $actor): array
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
    public function base(User $actor): Builder
    {
        return $this->access->directoryScope(User::query(), $actor);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function listing(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'showcase_dataset_id',
                'name',
                'email',
                'phone',
                'status',
                'force_password_reset',
                'last_login_at',
                'created_at',
            ])
            ->with([
                'portfolio:id,name_en,name_ar,code',
                'roles:id,name',
            ])
            ->withCount([
                'assignedMaintenanceRequests as open_assignments_count' => fn (Builder $requests) => $requests
                    ->whereIn('status', ['open', 'in_progress']),
            ]);
    }

    /**
     * @param  Builder<User>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters): void
    {
        $this->applyPortfolio($query, $filters);
        $this->tables->exact($query, $filters, 'status');
        $this->applyRole($query, (string) $filters['role']);
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

    /**
     * @param  Builder<User>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyPortfolio(Builder $query, array $filters): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');
    }

    /** @param Builder<User> $query */
    public function applyRole(Builder $query, string $role): void
    {
        if ($role !== 'all') {
            $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', $role));
        }
    }
}
