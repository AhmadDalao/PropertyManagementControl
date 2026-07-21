<?php

namespace App\Modules\Tenants\Queries;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use App\Modules\Tenants\Support\TenantAccess;
use Illuminate\Database\Eloquent\Builder;

class TenantSearch extends ModuleSearchSource
{
    public function __construct(
        private readonly TenantAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
        private readonly SearchResultPresenter $results,
    ) {}

    public function results(User $actor, string $query): array
    {
        if (! $this->isManager($actor) || ! $this->moduleEnabled($actor, 'tenants')) {
            return [];
        }

        $this->access->ensureManager($actor);
        $tenants = $this->portfolios
            ->apply(TenantProfile::query(), $actor)
            ->with('user');
        $this->tables->search($tenants, $query, [
            'national_id',
            'company_name',
            fn (Builder $tenants, string $term, string $like) => $tenants->orWhereHas(
                'user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like),
            ),
        ]);

        return $tenants
            ->limit(5)
            ->get()
            ->map(fn (TenantProfile $tenant): array => $this->results->result(
                trans('app.nav.tenants'),
                data_get($tenant, 'user.name') ?: trans('app.nav.tenants').' #'.$tenant->id,
                data_get($tenant, 'user.email') ?: $tenant->company_name,
                $this->results->status($tenant->status),
                route('tenants.show', $tenant),
            ))
            ->all();
    }
}
