<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait InteractsWithPortfolioScope
{
    protected function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    protected function requireRoles(User $user, array $roles): void
    {
        if (! $user->hasAnyRole($roles)) {
            throw new HttpException(403, 'You are not allowed to access this section.');
        }
    }

    protected function scopeByPortfolio(Builder $query, User $user, string $column = 'portfolio_id'): Builder
    {
        if ($user->hasRole('superadmin')) {
            return $query;
        }

        return $query->where($column, $user->portfolio_id ?? 0);
    }

    protected function ensurePortfolioAccess(User $user, ?int $portfolioId): void
    {
        if ($user->hasRole('superadmin')) {
            return;
        }

        if ($portfolioId === null || $user->portfolio_id !== $portfolioId) {
            throw new HttpException(403, 'You are not allowed to access this portfolio.');
        }
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    protected function portfolioOptions(User $user): array
    {
        return $this->scopeByPortfolio(Portfolio::query()->orderBy('name_en'), $user)
            ->get()
            ->map(fn (Portfolio $portfolio) => [
                'id' => $portfolio->id,
                'name' => $portfolio->name_en,
            ])
            ->all();
    }
}
