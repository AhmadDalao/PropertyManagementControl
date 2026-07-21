<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceSearch extends ModuleSearchSource
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

        $requests = $this->query($actor)->with(['asset', 'tenantProfile.user']);
        $this->tables->search($requests, $query, [
            'title',
            'description',
            'category',
            fn (Builder $requests, string $term, string $like) => $requests->orWhereHas(
                'asset',
                fn (Builder $assets) => $assets
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like),
            ),
            fn (Builder $requests, string $term, string $like) => $requests->orWhereHas(
                'tenantProfile.user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like),
            ),
        ]);

        return $requests
            ->limit(5)
            ->get()
            ->map(fn (MaintenanceRequest $request): array => $this->results->result(
                $actor->hasRole('tenant') ? trans('app.search.my_maintenance') : trans('app.nav.maintenance'),
                '#'.$request->id.' '.$request->title,
                $this->results->localized($request->asset?->title_en, $request->asset?->title_ar)
                    ?? $request->tenantProfile?->user?->name,
                $this->results->status($request->status),
                route('maintenance-requests.show', $request),
            ))
            ->all();
    }

    public function directUrl(User $actor, string $query): ?string
    {
        if (! $this->supports($actor) || ! ctype_digit($query)) {
            return null;
        }

        $request = $this->query($actor)->whereKey((int) $query)->first();

        return $request ? route('maintenance-requests.show', $request) : null;
    }

    private function supports(User $actor): bool
    {
        return ($this->isManager($actor) || $actor->hasRole('tenant'))
            && $this->moduleEnabled($actor, 'maintenance');
    }

    /** @return Builder<MaintenanceRequest> */
    private function query(User $actor): Builder
    {
        if ($actor->hasRole('tenant')) {
            return MaintenanceRequest::query()->whereHas(
                'tenantProfile',
                fn (Builder $tenants) => $tenants->where('user_id', $actor->id),
            );
        }

        return $this->portfolios->apply(MaintenanceRequest::query(), $actor);
    }
}
