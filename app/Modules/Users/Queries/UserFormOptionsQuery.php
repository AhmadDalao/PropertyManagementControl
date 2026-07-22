<?php

namespace App\Modules\Users\Queries;

use App\Models\Portfolio;
use App\Modules\Shared\ResourcePresenter;

final class UserFormOptionsQuery
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<int, array{value:int,label:string}> */
    public function activePortfolios(): array
    {
        $nameColumn = app()->isLocale('ar') ? 'name_ar' : 'name_en';

        return Portfolio::query()
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
