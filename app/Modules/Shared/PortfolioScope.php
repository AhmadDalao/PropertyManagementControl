<?php

namespace App\Modules\Shared;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PortfolioScope
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query, User $actor, string $column = 'portfolio_id'): Builder
    {
        return $actor->hasRole('superadmin')
            ? $query
            : $query->where($column, $actor->portfolio_id ?? 0);
    }

    public function ensureAccess(User $actor, ?int $portfolioId): void
    {
        if (! $actor->hasRole('superadmin') && ($portfolioId === null || $actor->portfolio_id !== $portfolioId)) {
            throw new HttpException(403, trans('app.errors.portfolio_access_denied'));
        }
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function options(User $actor): array
    {
        $nameColumn = app()->isLocale('ar') ? 'name_ar' : 'name_en';

        return $this->apply(Portfolio::query()->orderBy($nameColumn), $actor, 'id')
            ->get()
            ->map(fn (Portfolio $portfolio) => [
                'id' => $portfolio->id,
                'name' => $this->localized($portfolio->name_en, $portfolio->name_ar),
            ])
            ->all();
    }

    public function localized(?string $english, ?string $arabic): ?string
    {
        $primary = app()->isLocale('ar') ? $arabic : $english;
        $fallback = app()->isLocale('ar') ? $english : $arabic;
        $value = trim((string) ($primary ?: $fallback));

        return $value === '' ? null : $value;
    }
}
