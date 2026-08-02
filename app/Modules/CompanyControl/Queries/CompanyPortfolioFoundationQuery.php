<?php

namespace App\Modules\CompanyControl\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Collection;

final class CompanyPortfolioFoundationQuery
{
    /** @return Collection<int, array<string, mixed>> */
    public function get(): Collection
    {
        $portfolios = Portfolio::query()
            ->with('owner:id,name,status')
            ->orderBy('name_en')
            ->get();
        $ids = $portfolios->pluck('id')->all();
        $users = User::query()
            ->with('roles:id,name')
            ->whereIn('portfolio_id', $ids)
            ->get(['id', 'portfolio_id', 'status']);
        $roots = Asset::query()
            ->with('currentStakeholders:id,asset_id,relationship_type,is_primary')
            ->whereIn('portfolio_id', $ids)
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->get(['id', 'portfolio_id']);
        $leases = Lease::query()
            ->whereIn('portfolio_id', $ids)
            ->whereIn('status', ['draft', 'active'])
            ->get(['id', 'portfolio_id', 'status', 'ends_at', 'terms_json']);
        $valuations = Asset::query()
            ->whereIn('portfolio_id', $ids)
            ->where('status', '!=', 'archived')
            ->selectRaw('portfolio_id, currency, SUM(valuation_amount) as total')
            ->groupBy('portfolio_id', 'currency')
            ->get()
            ->groupBy('portfolio_id');

        return $portfolios->map(fn (Portfolio $portfolio): array => $this->row(
            $portfolio,
            $users->where('portfolio_id', $portfolio->id),
            $roots->where('portfolio_id', $portfolio->id),
            $leases->where('portfolio_id', $portfolio->id),
            $valuations->get($portfolio->id, collect()),
        ));
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Asset>  $roots
     * @param  Collection<int, Lease>  $leases
     * @param  Collection<int, Asset>  $valuations
     * @return array<string, mixed>
     */
    private function row(
        Portfolio $portfolio,
        Collection $users,
        Collection $roots,
        Collection $leases,
        Collection $valuations,
    ): array {
        $active = $users->where('status', 'active');
        $owners = $this->roleCount($active, 'owner');
        $managers = $this->roleCount($active, 'property_manager');
        $tenants = $this->roleCount($active, 'tenant');
        $assignmentGaps = $roots->sum(function (Asset $asset): int {
            $primary = $asset->currentStakeholders->where('is_primary', true);

            return (int) ! $primary->contains('relationship_type', 'owner')
                + (int) ! $primary->contains('relationship_type', 'manager');
        });
        $missingTerms = $leases->filter(
            fn (Lease $lease): bool => trim((string) data_get($lease->terms_json, 'en')) === ''
                || trim((string) data_get($lease->terms_json, 'ar')) === '',
        )->count();
        $checks = [
            $portfolio->status === 'active' ? 'ready' : 'blocked',
            $portfolio->owner?->status === 'active' && $owners > 0 ? 'ready' : 'blocked',
            $managers > 0 ? 'ready' : 'blocked',
            $tenants > 0 ? 'ready' : 'attention',
            $roots->isNotEmpty() ? 'ready' : 'blocked',
            $assignmentGaps === 0 ? 'ready' : 'attention',
            $leases->isEmpty() ? 'attention' : ($missingTerms === 0 ? 'ready' : 'blocked'),
            $portfolio->is_showcase ? 'blocked' : 'ready',
        ];
        $blocked = count(array_filter($checks, fn (string $status): bool => $status === 'blocked'));
        $attention = count(array_filter($checks, fn (string $status): bool => $status === 'attention'));

        return [
            'id' => $portfolio->id,
            'code' => $portfolio->code,
            'name_en' => $portfolio->name_en,
            'name_ar' => $portfolio->name_ar,
            'status' => $portfolio->status,
            'is_showcase' => $portfolio->is_showcase,
            'default_currency' => $portfolio->default_currency,
            'owner' => $portfolio->owner ? [
                'id' => $portfolio->owner->id,
                'name' => $portfolio->owner->name,
            ] : null,
            'accounts' => [
                'active' => $active->count(),
                'owners' => $owners,
                'managers' => $managers,
                'tenants' => $tenants,
            ],
            'valuation_totals' => $valuations
                ->map(fn (Asset $valuation): array => [
                    'currency' => strtoupper((string) $valuation->getAttribute('currency')),
                    'amount' => round((float) $valuation->getAttribute('total'), 2),
                ])
                ->sortBy('currency')
                ->values()
                ->all(),
            'readiness' => [
                'score' => round((count(array_filter($checks, fn (string $status): bool => $status === 'ready')) / count($checks)) * 100),
                'status' => $blocked > 0 ? 'blocked' : ($attention > 0 ? 'attention' : 'ready'),
                'blocked' => $blocked,
                'attention' => $attention,
                'assignment_gaps' => $assignmentGaps,
                'missing_terms' => $missingTerms,
            ],
        ];
    }

    /** @param Collection<int, User> $users */
    private function roleCount(Collection $users, string $role): int
    {
        return $users->filter(
            fn (User $user): bool => $user->roles->contains('name', $role),
        )->count();
    }
}
