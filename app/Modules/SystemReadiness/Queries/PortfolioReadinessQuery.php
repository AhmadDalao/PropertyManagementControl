<?php

namespace App\Modules\SystemReadiness\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\SystemReadiness\Presenters\PortfolioReadinessActionPresenter;
use App\Modules\SystemReadiness\Support\ReadinessLocale;
use Illuminate\Database\Eloquent\Builder;

final class PortfolioReadinessQuery
{
    public function __construct(
        private readonly ReadinessLocale $locale,
        private readonly PortfolioReadinessActionPresenter $actions,
    ) {}

    /**
     * @return array{
     *     options: array<int, array<string, mixed>>,
     *     selected: ?array<string, mixed>,
     *     launch: array{live_portfolios: int, needs_live_portfolio: bool, create_href: string}
     * }
     */
    public function handle(?int $requestedPortfolioId): array
    {
        $portfolios = Portfolio::query()
            ->with('owner:id,name,status')
            ->where('status', '!=', 'archived')
            ->orderByRaw('showcase_dataset_id is not null')
            ->orderBy('name_en')
            ->get();
        $livePortfolioCount = $portfolios
            ->reject(fn (Portfolio $portfolio): bool => $portfolio->is_showcase)
            ->count();

        $selected = $requestedPortfolioId
            ? $portfolios->firstWhere('id', $requestedPortfolioId)
            : $portfolios->first(
                fn (Portfolio $portfolio): bool => ! $portfolio->is_showcase,
            );

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
            'launch' => [
                'live_portfolios' => $livePortfolioCount,
                'needs_live_portfolio' => $livePortfolioCount === 0,
                'create_href' => route('portfolios.create'),
            ],
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
        $ownerReady = $hasOwner && $owners > 0;

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
                $this->check(
                    'portfolio_status',
                    $portfolio->status === 'active' ? 'ready' : 'blocked',
                    $this->actions->portfolio($portfolio, $portfolio->status === 'active'),
                ),
                $this->check(
                    'portfolio_owner',
                    $ownerReady ? 'ready' : 'blocked',
                    $this->actions->owner($portfolio, $ownerReady),
                ),
                $this->check(
                    'operations_team',
                    $managers > 0 ? 'ready' : 'blocked',
                    $this->actions->manager($portfolio, $managers > 0),
                ),
                $this->check(
                    'tenant_access',
                    $tenants > 0 ? 'ready' : 'attention',
                    $this->actions->tenant($portfolio, $tenants > 0),
                ),
                $this->check(
                    'property_register',
                    $assetCount > 0 ? 'ready' : 'blocked',
                    $this->actions->property($portfolio, $assetCount > 0),
                ),
                $this->check(
                    'asset_assignments',
                    $missingOwners + $missingManagers === 0 ? 'ready' : 'attention',
                    $this->actions->assignments($portfolio),
                    ['count' => $missingOwners + $missingManagers],
                ),
                $this->check(
                    'bilingual_terms',
                    $leases->isEmpty()
                        ? 'attention'
                        : ($missingEnglishTerms + $missingArabicTerms === 0 ? 'ready' : 'blocked'),
                    $this->actions->leases($portfolio, $leases->isNotEmpty()),
                    ['count' => $missingEnglishTerms + $missingArabicTerms],
                ),
                $this->check(
                    'showcase',
                    $portfolio->is_showcase ? 'blocked' : 'ready',
                    $this->actions->dataSource($portfolio),
                ),
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
     * @param  array{href: string, label: string}  $action
     * @param  array<string, int>  $replacements
     * @return array<string, mixed>
     */
    private function check(
        string $key,
        string $status,
        array $action,
        array $replacements = [],
    ): array {
        $localized = array_map(
            fn (int $value): string => $this->locale->number($value),
            $replacements,
        );

        return [
            'key' => $key,
            'label' => trans("app.readiness.portfolio_checks.{$key}.label"),
            'description' => trans("app.readiness.portfolio_checks.{$key}.description", $localized),
            'status' => $status,
            'href' => $action['href'],
            'action_label' => $action['label'],
        ];
    }
}
