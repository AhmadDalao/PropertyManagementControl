<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\Asset;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Collection;

class MaintenanceFormOptionsQuery
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly MorphTypes $morphTypes,
    ) {}

    /** @return Collection<int, Asset> */
    public function tenantAssets(User $actor): Collection
    {
        $tenant = TenantProfile::query()->where('user_id', $actor->id)->first();

        if (! $tenant) {
            return collect();
        }

        return $tenant->leases()
            ->where('status', 'active')
            ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
            ->with('leaseable')
            ->get()
            ->pluck('leaseable')
            ->filter(fn (mixed $asset): bool => $asset instanceof Asset)
            ->unique('id')
            ->values();
    }

    /** @return Collection<int, Asset> */
    public function managerAssets(User $actor): Collection
    {
        return $this->portfolios
            ->apply(Asset::query()->orderBy('title_en'), $actor)
            ->get();
    }

    /** @return Collection<int, TenantProfile> */
    public function managerTenants(User $actor): Collection
    {
        return $this->portfolios
            ->apply(TenantProfile::query()->with('user')->orderBy('id'), $actor)
            ->get();
    }

    /** @return Collection<int, User> */
    public function managers(User $actor): Collection
    {
        return $this->portfolios->apply(
            User::query()
                ->whereHas(
                    'roles',
                    fn ($roles) => $roles->whereIn('name', ['owner', 'property_manager']),
                )
                ->orderBy('name'),
            $actor,
        )->get();
    }

    /** @return array<int, array{id:int,name:string}> */
    public function portfolios(User $actor): array
    {
        return $this->portfolios->options($actor);
    }
}
