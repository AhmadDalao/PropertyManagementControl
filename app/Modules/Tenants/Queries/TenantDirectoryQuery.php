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

final class TenantDirectoryQuery
{
    public function __construct(
        private readonly TenantAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        $filters = $this->tables->filters($request, [
            'status' => 'all',
            'profile_type' => 'all',
        ]);

        if (! in_array($filters['status'], ['all', ...TenantOptions::STATUSES], true)) {
            $filters['status'] = 'all';
        }

        if (! in_array($filters['profile_type'], ['all', ...TenantOptions::PROFILE_TYPES], true)) {
            $filters['profile_type'] = 'all';
        }

        return $filters;
    }

    /** @return Builder<TenantProfile> */
    public function base(User $actor): Builder
    {
        $this->access->ensureManager($actor);

        return $this->portfolios->apply(TenantProfile::query(), $actor);
    }

    /**
     * @param  Builder<TenantProfile>  $query
     * @return Builder<TenantProfile>
     */
    public function listing(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'user_id',
                'profile_type',
                'national_id',
                'company_name',
                'status',
                'created_at',
            ])
            ->selectRaw("CASE WHEN emergency_contact_name IS NULL OR emergency_contact_name = '' OR emergency_contact_phone IS NULL OR emergency_contact_phone = '' THEN 1 ELSE 0 END as missing_emergency")
            ->selectRaw("CASE WHEN address IS NULL OR address = '' THEN 1 ELSE 0 END as missing_address")
            ->with([
                'portfolio:id,showcase_dataset_id',
                'user:id,portfolio_id,name,email,phone,status',
            ])
            ->withCount([
                'leases',
                'leases as active_leases_count' => fn (Builder $leases) => $leases->where('status', 'active'),
                'maintenanceRequests as open_requests_count' => fn (Builder $requests) => $requests
                    ->whereIn('status', ['open', 'in_progress']),
            ]);
    }

    /**
     * @param  Builder<TenantProfile>  $query
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters): void
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
            fn (Builder $tenants, string $search, string $like) => $tenants->orWhereHas(
                'user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like),
            ),
        ]);
    }

    /**
     * @param  Builder<TenantProfile>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyPortfolio(Builder $query, array $filters): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');
    }
}
