<?php

namespace App\Modules\Leases\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Leases\Data\LeaseFormData;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Database\Eloquent\Builder;

final class LeaseFormOptionsQuery
{
    public function __construct(
        private readonly PortfolioScope $portfolioScope,
        private readonly ResourcePresenter $resources,
        private readonly MorphTypes $morphTypes,
    ) {}

    /** @param array<string, mixed> $defaults */
    public function get(User $actor, ?Lease $lease = null, array $defaults = []): LeaseFormData
    {
        $portfolios = $this->activePortfolios($actor);
        $requested = filter_var($defaults['portfolio_id'] ?? $actor->portfolio_id, FILTER_VALIDATE_INT);
        $portfolioId = collect($portfolios)->contains('value', $requested)
            ? (int) $requested
            : $this->defaultPortfolioId($actor, $portfolios);

        return new LeaseFormData(
            actor: $actor,
            lease: $lease,
            defaults: $defaults,
            portfolioId: $portfolioId,
            portfolios: $portfolios,
            tenants: $lease || ! $portfolioId ? [] : $this->activeTenants($portfolioId),
            assets: $lease || ! $portfolioId ? [] : $this->availableAssets($portfolioId),
        );
    }

    /** @return array<int, array{value:int,label:string}> */
    private function activePortfolios(User $actor): array
    {
        $nameColumn = app()->isLocale('ar') ? 'name_ar' : 'name_en';

        return $this->portfolioScope->apply(Portfolio::query(), $actor, 'id')
            ->where('status', 'active')
            ->orderBy($nameColumn)
            ->get(['id', 'name_en', 'name_ar', 'code'])
            ->map(fn (Portfolio $portfolio): array => [
                'value' => $portfolio->id,
                'label' => trim(($this->resources->localized($portfolio->name_en, $portfolio->name_ar) ?? '').' · '.$portfolio->code),
            ])->all();
    }

    /** @return array<int, array{value:int,label:string}> */
    private function activeTenants(int $portfolioId): array
    {
        return TenantProfile::query()
            ->where('portfolio_id', $portfolioId)
            ->where('status', 'active')
            ->whereHas('user', fn (Builder $users) => $users->where('status', 'active'))
            ->with('user:id,name')
            ->orderBy('id')
            ->get(['id', 'user_id'])
            ->map(fn (TenantProfile $tenant): array => [
                'value' => $tenant->id,
                'label' => $tenant->user->name ?? trans('app.leases.tenant_number', ['id' => $tenant->id]),
            ])->all();
    }

    /** @return array<int, array{value:int,label:string}> */
    private function availableAssets(int $portfolioId): array
    {
        $leasedAssetIds = Lease::query()
            ->select('leaseable_id')
            ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
            ->where('status', 'active');
        $titleColumn = app()->isLocale('ar') ? 'title_ar' : 'title_en';

        return Asset::query()
            ->where('portfolio_id', $portfolioId)
            ->where('status', 'active')
            ->where('rentable', true)
            ->whereNotIn('id', $leasedAssetIds)
            ->orderBy($titleColumn)
            ->get(['id', 'title_en', 'title_ar', 'code'])
            ->map(fn (Asset $asset): array => [
                'value' => $asset->id,
                'label' => trim(($this->resources->localized($asset->title_en, $asset->title_ar) ?? '').' · '.$asset->code),
            ])->all();
    }

    /** @param array<int, array{value:int,label:string}> $portfolios */
    private function defaultPortfolioId(User $actor, array $portfolios): ?int
    {
        if (! $actor->hasRole('superadmin')) {
            return $portfolios[0]['value'] ?? null;
        }

        $portfolioIds = collect($portfolios)->pluck('value')->all();

        if ($portfolioIds === []) {
            return null;
        }

        $leasedAssetIds = Lease::query()
            ->select('leaseable_id')
            ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
            ->where('status', 'active');
        $candidate = Portfolio::query()
            ->whereIn('id', $portfolioIds)
            ->whereHas('tenantProfiles', fn (Builder $tenants) => $tenants
                ->where('status', 'active')
                ->whereHas('user', fn (Builder $users) => $users->where('status', 'active')))
            ->whereHas('assets', fn (Builder $assets) => $assets
                ->where('status', 'active')
                ->where('rentable', true)
                ->whereNotIn('id', $leasedAssetIds))
            ->orderBy(app()->isLocale('ar') ? 'name_ar' : 'name_en')
            ->value('id');

        return $candidate ? (int) $candidate : ($portfolios[0]['value'] ?? null);
    }
}
