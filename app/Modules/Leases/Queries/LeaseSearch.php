<?php

namespace App\Modules\Leases\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;

class LeaseSearch extends ModuleSearchSource
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
        private readonly SearchResultPresenter $results,
    ) {}

    public function results(User $actor, string $query): array
    {
        if (! $this->supports($actor)) {
            return [];
        }

        $leases = $this->query($actor)->with(['tenantProfile.user', 'leaseable']);
        $this->tables->search($leases, $query, [
            'code',
            'notes',
            fn (Builder $leases, string $term, string $like) => $leases->orWhereHas(
                'tenantProfile.user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like),
            ),
            fn (Builder $leases, string $term, string $like) => $leases->orWhereHasMorph(
                'leaseable',
                [Asset::class],
                fn (Builder $assets) => $assets
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like),
            ),
        ]);

        return $leases
            ->limit(5)
            ->get()
            ->map(function (Lease $lease) use ($actor): array {
                $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;
                $tenant = $lease->tenantProfile?->user?->name;
                $assetTitle = $this->results->localized($asset?->title_en, $asset?->title_ar);
                $subtitle = $actor->hasRole('tenant')
                    ? $assetTitle
                    : collect([$tenant, $assetTitle])->filter()->join(' · ');

                return $this->results->result(
                    $actor->hasRole('tenant') ? trans('app.search.my_leases') : trans('app.nav.leases'),
                    $lease->code,
                    $subtitle,
                    $this->results->status($lease->status),
                    route('leases.show', $lease),
                );
            })
            ->all();
    }

    public function directUrl(User $actor, string $query): ?string
    {
        if (! $this->supports($actor)) {
            return null;
        }

        $lease = $this->query($actor)->where('code', $query)->first();

        return $lease ? route('leases.show', $lease) : null;
    }

    private function supports(User $actor): bool
    {
        return ($this->isManager($actor) || $actor->hasRole('tenant'))
            && $this->moduleEnabled($actor, 'leases');
    }

    /** @return Builder<Lease> */
    private function query(User $actor): Builder
    {
        if ($actor->hasRole('tenant')) {
            return Lease::query()->whereHas(
                'tenantProfile',
                fn (Builder $tenants) => $tenants->where('user_id', $actor->id),
            );
        }

        return $this->portfolios->apply(Lease::query(), $actor);
    }
}
