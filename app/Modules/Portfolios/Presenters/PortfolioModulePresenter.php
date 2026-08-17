<?php

namespace App\Modules\Portfolios\Presenters;

use App\Modules\Portfolios\Data\PortfolioDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;

final class PortfolioModulePresenter
{
    /** @return list<array{key:string,label:string,description:string,enabled:bool}> */
    public function present(PortfolioDetailData $data): array
    {
        $modules = [];

        foreach (PortfolioModules::definitions() as $module) {
            $modules[] = [
                ...$module,
                'enabled' => $data->settings[$module['key']] ?? true,
            ];
        }

        return $modules;
    }
}
