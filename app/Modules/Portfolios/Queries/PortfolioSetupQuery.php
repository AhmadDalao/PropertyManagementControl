<?php

namespace App\Modules\Portfolios\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class PortfolioSetupQuery
{
    /**
     * @return array{
     *     portfolio: bool,
     *     owner: bool,
     *     manager: bool,
     *     property: bool,
     *     tenant: bool,
     *     lease: bool
     * }
     */
    public function handle(Portfolio $portfolio): array
    {
        return [
            'portfolio' => $portfolio->status === 'active',
            'owner' => $portfolio->owner?->status === 'active',
            'manager' => $this->hasRole($portfolio, 'property_manager'),
            'property' => Asset::query()
                ->where('portfolio_id', $portfolio->id)
                ->whereNull('parent_id')
                ->where('status', '!=', 'archived')
                ->exists(),
            'tenant' => $this->hasRole($portfolio, 'tenant'),
            'lease' => Lease::query()
                ->where('portfolio_id', $portfolio->id)
                ->where('status', 'active')
                ->exists(),
        ];
    }

    private function hasRole(Portfolio $portfolio, string $role): bool
    {
        return User::query()
            ->where('portfolio_id', $portfolio->id)
            ->where('status', 'active')
            ->whereHas('roles', fn (Builder $roles): Builder => $roles->where('name', $role))
            ->exists();
    }
}
