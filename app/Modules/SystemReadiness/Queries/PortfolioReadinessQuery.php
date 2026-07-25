<?php

namespace App\Modules\SystemReadiness\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\SystemReadiness\Support\ReadinessLocale;
use Illuminate\Database\Eloquent\Builder;

final class PortfolioReadinessQuery
{
    public function __construct(private readonly ReadinessLocale $locale) {}

    /** @return array{options: array<int, array<string, mixed>>, selected: ?array<string, mixed>} */
    public function handle(?int $requestedPortfolioId): array
    {
        $portfolios = Portfolio::query()
            ->with('owner:id,name,status')
            ->orderByRaw('showcase_dataset_id is not null')
            ->orderBy('name_en')
            ->get();

        $selected = $requestedPortfolioId
            ? $portfolios->firstWhere('id', $requestedPortfolioId)
            : $portfolios->first();

        return [
            'options' => $portfolios
                ->map(fn (Portfolio $portfolio): array => [
                    'id' => $portfolio->id,
                    'name' => app()->getLocale() === 'ar' ? $portfolio->name_ar : $portfolio->name_en,
                    'code' => $portfolio->code,
                    'is_showcase' => $portfolio->is_showcase,
                ])
                ->values()
                ->all(),
            'selected' => $selected ? $this->present($selected) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function present(Portfolio $portfolio): array
    {
        $activeUsers = User::query()
            ->where('portfolio_id', $portfolio->id)
            ->where('status', 'active');
        $owners = $this->roleCount(clone $activeUsers, 'owner');
        $managers = $this->roleCount(clone $activeUsers, 'property_manager');
        $tenants = $this->roleCount(clone $activeUsers, 'tenant');
        $topAssets = Asset::query()
            ->where('portfolio_id', $portfolio->id)
            ->whereNull('parent_id');
        $assetCount = (clone $topAssets)->count();
        $missingOwners = $this->missingStakeholderCount(clone $topAssets, 'owner');
        $missingManagers = $this->missingStakeholderCount(clone $topAssets, 'manager');
        $leases = Lease::query()
            ->where('portfolio_id', $portfolio->id)
            ->whereIn('status', ['draft', 'active'])
            ->get(['id', 'terms_json']);
        $missingEnglishTerms = $leases->filter(fn (Lease $lease): bool => trim((string) data_get($lease->terms_json, 'en')) === '')->count();
        $missingArabicTerms = $leases->filter(fn (Lease $lease): bool => trim((string) data_get($lease->terms_json, 'ar')) === '')->count();
        $hasOwner = $portfolio->owner !== null && $portfolio->owner->status === 'active';

        return [
            'portfolio' => [
                'id' => $portfolio->id,
                'name' => app()->getLocale() === 'ar' ? $portfolio->name_ar : $portfolio->name_en,
                'code' => $portfolio->code,
                'status' => $portfolio->status,
                'is_showcase' => $portfolio->is_showcase,
            ],
            'metrics' => [
                'owners' => $owners,
                'managers' => $managers,
                'tenants' => $tenants,
                'properties' => $assetCount,
                'current_leases' => $leases->count(),
                'assignment_gaps' => $missingOwners + $missingManagers,
            ],
            'checks' => [
                $this->check('portfolio_status', $portfolio->status === 'active' ? 'ready' : 'blocked', '/portfolios/'.$portfolio->id),
                $this->check('portfolio_owner', $hasOwner && $owners > 0 ? 'ready' : 'blocked', '/users?portfolio_id='.$portfolio->id),
                $this->check('operations_team', $managers > 0 ? 'ready' : 'blocked', '/users?portfolio_id='.$portfolio->id),
                $this->check('tenant_access', $tenants > 0 ? 'ready' : 'attention', '/users?portfolio_id='.$portfolio->id),
                $this->check('property_register', $assetCount > 0 ? 'ready' : 'blocked', '/assets?portfolio_id='.$portfolio->id),
                $this->check(
                    'asset_assignments',
                    $missingOwners + $missingManagers === 0 ? 'ready' : 'attention',
                    '/assets?portfolio_id='.$portfolio->id,
                    ['count' => $missingOwners + $missingManagers],
                ),
                $this->check(
                    'bilingual_terms',
                    $leases->isEmpty()
                        ? 'attention'
                        : ($missingEnglishTerms + $missingArabicTerms === 0 ? 'ready' : 'blocked'),
                    '/leases?portfolio_id='.$portfolio->id,
                    ['count' => $missingEnglishTerms + $missingArabicTerms],
                ),
                $this->check('showcase', $portfolio->is_showcase ? 'blocked' : 'ready', '/system/showcase-data'),
            ],
        ];
    }

    /** @param Builder<User> $query */
    private function roleCount(Builder $query, string $role): int
    {
        return $query
            ->whereHas('roles', fn (Builder $roles): Builder => $roles->where('name', $role))
            ->count();
    }

    /** @param Builder<Asset> $query */
    private function missingStakeholderCount(Builder $query, string $relationship): int
    {
        return $query
            ->whereDoesntHave(
                'currentStakeholders',
                fn (Builder $stakeholders): Builder => $stakeholders
                    ->where('relationship_type', $relationship)
                    ->where('is_primary', true),
            )
            ->count();
    }

    /**
     * @param  array<string, int>  $replacements
     * @return array<string, mixed>
     */
    private function check(string $key, string $status, string $href, array $replacements = []): array
    {
        $localized = array_map(
            fn (int $value): string => $this->locale->number($value),
            $replacements,
        );

        return [
            'key' => $key,
            'label' => trans("app.readiness.portfolio_checks.{$key}.label"),
            'description' => trans("app.readiness.portfolio_checks.{$key}.description", $localized),
            'status' => $status,
            'href' => $href,
        ];
    }
}
