<?php

namespace App\Modules\Tenants\Queries;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;

final class TenantFormOptionsQuery
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<int, array{value:int,label:string}> */
    public function activePortfolios(User $actor): array
    {
        $nameColumn = app()->isLocale('ar') ? 'name_ar' : 'name_en';

        return $this->portfolios->apply(Portfolio::query(), $actor, 'id')
            ->where('status', 'active')
            ->orderBy($nameColumn)
            ->get(['id', 'name_en', 'name_ar', 'code'])
            ->map(fn (Portfolio $portfolio): array => [
                'value' => $portfolio->id,
                'label' => trim(($this->resources->localized(
                    $portfolio->name_en,
                    $portfolio->name_ar,
                ) ?? '').' · '.$portfolio->code),
            ])
            ->all();
    }
}
