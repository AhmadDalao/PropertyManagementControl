<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AssetReferenceGuard
{
    public function __construct(private readonly AssetHierarchy $hierarchy) {}

    public function ensureActivePortfolio(int $portfolioId): void
    {
        $portfolio = Portfolio::query()->lockForUpdate()->find($portfolioId);

        if (! $portfolio || $portfolio->status !== 'active') {
            $this->fail('portfolio_id', trans('app.errors.asset_portfolio_inactive'));
        }
    }

    /** @param array<string, mixed> $data */
    public function ensure(array $data, int $portfolioId, ?Asset $current = null): void
    {
        $this->ensureParent($data['parent_id'] ?? null, $portfolioId, $current);
        $this->ensureStakeholder(
            $data['primary_owner_user_id'] ?? null,
            $portfolioId,
            'primary_owner_user_id',
            ['owner'],
            trans('app.errors.owner_assignment_invalid'),
        );
        $this->ensureStakeholder(
            $data['primary_manager_user_id'] ?? null,
            $portfolioId,
            'primary_manager_user_id',
            ['owner', 'property_manager'],
            trans('app.errors.manager_assignment_invalid'),
        );
    }

    private function ensureParent(mixed $parentId, int $portfolioId, ?Asset $current): void
    {
        if (! filled($parentId)) {
            return;
        }

        $parentId = (int) $parentId;

        if ($current && $parentId === $current->id) {
            $this->fail('parent_id', trans('app.errors.asset_self_parent'));
        }

        if ($current && in_array($parentId, $this->hierarchy->descendantIdsIncluding($current), true)) {
            $this->fail('parent_id', trans('app.errors.asset_descendant_parent'));
        }

        $parent = Asset::query()->lockForUpdate()->find($parentId);

        if (! $parent || $parent->portfolio_id !== $portfolioId) {
            $this->fail('parent_id', trans('app.errors.parent_asset_portfolio_mismatch'));
        }

        if ($parent->status === 'archived') {
            $this->fail('parent_id', trans('app.errors.parent_asset_archived'));
        }
    }

    /** @param array<int, string> $roles */
    private function ensureStakeholder(
        mixed $userId,
        int $portfolioId,
        string $field,
        array $roles,
        string $message,
    ): void {
        if (! filled($userId)) {
            return;
        }

        $exists = User::query()
            ->whereKey((int) $userId)
            ->where('portfolio_id', $portfolioId)
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', $roles))
            ->exists();

        if (! $exists) {
            $this->fail($field, $message);
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
