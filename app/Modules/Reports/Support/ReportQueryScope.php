<?php

namespace App\Modules\Reports\Support;

use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

final readonly class ReportQueryScope
{
    public function __construct(private PortfolioScope $portfolios) {}

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query, User $actor, ?int $portfolioId): Builder
    {
        $this->portfolios->apply($query, $actor);

        if ($portfolioId !== null) {
            $query->where('portfolio_id', $portfolioId);
        }

        return $query;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function withinDateRange(
        Builder $query,
        string $column,
        string $dateFrom,
        string $dateTo,
    ): Builder {
        return $query
            ->whereDate($column, '>=', $dateFrom)
            ->whereDate($column, '<=', $dateTo);
    }
}
