<?php

namespace App\Modules\Portfolios\Queries;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;

class PortfolioSearch extends ModuleSearchSource
{
    public function __construct(
        private readonly PortfolioIndexQuery $portfolios,
        private readonly SearchResultPresenter $results,
    ) {}

    public function results(User $actor, string $query): array
    {
        if (! $this->isManager($actor)) {
            return [];
        }

        return collect($this->portfolios->search($actor, $query))
            ->map(fn (Portfolio $portfolio): array => $this->results->result(
                trans('app.nav.portfolios'),
                $this->results->localized($portfolio->name_en, $portfolio->name_ar),
                $portfolio->code,
                $this->results->status($portfolio->status),
                route('portfolios.show', $portfolio),
            ))
            ->all();
    }

    public function directUrl(User $actor, string $query): ?string
    {
        if (! $this->isManager($actor)) {
            return null;
        }

        $portfolio = $this->portfolios->exactCode($actor, $query);

        return $portfolio ? route('portfolios.show', $portfolio) : null;
    }
}
