<?php

namespace App\Modules\OpeningData\Support;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;

final class OpeningDataAccess
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function ensureOperator(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner']),
            403,
            trans('app.errors.section_access_denied'),
        );
    }

    public function portfolio(User $actor, int $portfolioId): Portfolio
    {
        $this->ensureOperator($actor);
        $this->portfolios->ensureAccess($actor, $portfolioId);

        $portfolio = Portfolio::query()
            ->whereKey($portfolioId)
            ->where('status', 'active')
            ->whereNull('showcase_dataset_id')
            ->first();

        abort_unless(
            $portfolio !== null,
            422,
            trans('app.opening_data.errors.portfolio_unavailable'),
        );

        return $portfolio;
    }

    /**
     * @return array<int, array{id:int,name:string,code:string}>
     */
    public function options(User $actor): array
    {
        $this->ensureOperator($actor);
        $nameColumn = app()->isLocale('ar') ? 'name_ar' : 'name_en';

        return $this->portfolios
            ->apply(Portfolio::query(), $actor, 'id')
            ->where('status', 'active')
            ->whereNull('showcase_dataset_id')
            ->orderBy($nameColumn)
            ->get(['id', 'name_en', 'name_ar', 'code'])
            ->map(fn (Portfolio $portfolio): array => [
                'id' => $portfolio->id,
                'name' => (string) $this->portfolios->localized(
                    $portfolio->name_en,
                    $portfolio->name_ar,
                ),
                'code' => $portfolio->code,
            ])
            ->all();
    }
}
