<?php

namespace App\Modules\Tenants\Queries;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use App\Modules\Tenants\Support\TenantAccess;
use App\Modules\Tenants\Support\TenantOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TenantIndexQuery
{
    public function __construct(
        private readonly TenantAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $this->access->ensureManager($actor);
        $filters = $this->filters($request);
        $baseQuery = $this->portfolios->apply(TenantProfile::query(), $actor);
        $tenants = $this->indexQuery(clone $baseQuery);
        $this->applyFilters($tenants, $filters);
        $metricScope = clone $baseQuery;
        $this->tables->exact($metricScope, $filters, 'portfolio_id');

        return [
            'tenants' => $this->tables->paginate($tenants, $filters, [
                'created_at',
                'status',
                'profile_type',
                'company_name',
            ]),
            'filters' => $filters,
            'counts' => $this->tables->statusCounts($metricScope, TenantOptions::STATUSES, $filters),
            'portfolioOptions' => $this->portfolios->options($actor),
            'tenantInsights' => $this->insights($metricScope),
            'profileTypeOptions' => TenantOptions::PROFILE_TYPES,
            'statusOptions' => TenantOptions::STATUSES,
        ];
    }

    /** @return Builder<TenantProfile> */
    public function forExport(Request $request, User $actor): Builder
    {
        $this->access->ensureManager($actor);
        $filters = $this->filters($request);
        $query = $this->portfolios->apply(TenantProfile::query(), $actor)
            ->with(['portfolio', 'user']);
        $this->applyFilters($query, $filters);

        return $query;
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $this->tables->filters($request, [
            'status' => 'all',
            'profile_type' => 'all',
        ]);
    }

    /**
     * @param  Builder<TenantProfile>  $query
     * @return Builder<TenantProfile>
     */
    private function indexQuery(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'user_id',
                'profile_type',
                'national_id',
                'company_name',
                'emergency_contact_name',
                'emergency_contact_phone',
                'address',
                'status',
                'created_at',
                'updated_at',
            ])
            ->with([
                'portfolio:id,showcase_dataset_id',
                'user:id,portfolio_id,name,email,phone,preferred_locale,status',
            ])
            ->withCount([
                'leases',
                'leases as active_leases_count' => fn (Builder $leases) => $leases->where('status', 'active'),
                'maintenanceRequests as open_requests_count' => fn (Builder $requests) => $requests->whereIn('status', ['open', 'in_progress']),
            ]);
    }

    /**
     * @param  Builder<TenantProfile>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach (['portfolio_id', 'status', 'profile_type'] as $filter) {
            $this->tables->exact($query, $filters, $filter);
        }

        $this->tables->search($query, (string) $filters['search'], [
            'national_id',
            'company_name',
            'emergency_contact_name',
            'emergency_contact_phone',
            'address',
            fn (Builder $query, string $search, string $like) => $query->orWhereHas(
                'user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like),
            ),
        ]);
    }

    /**
     * @param  Builder<TenantProfile>  $baseQuery
     * @return array<string, int>
     */
    private function insights(Builder $baseQuery): array
    {
        return [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'blocked' => (clone $baseQuery)->where('status', 'blocked')->count(),
            'companies' => (clone $baseQuery)->where('profile_type', 'company')->count(),
            'without_active_lease' => (clone $baseQuery)
                ->whereDoesntHave('leases', fn (Builder $leases) => $leases->where('status', 'active'))
                ->count(),
            'missing_emergency' => (clone $baseQuery)
                ->where(function (Builder $tenants): void {
                    $tenants
                        ->whereNull('emergency_contact_name')
                        ->orWhereNull('emergency_contact_phone')
                        ->orWhere('emergency_contact_name', '')
                        ->orWhere('emergency_contact_phone', '');
                })
                ->count(),
            'missing_address' => (clone $baseQuery)
                ->where(fn (Builder $tenants) => $tenants->whereNull('address')->orWhere('address', ''))
                ->count(),
        ];
    }
}
