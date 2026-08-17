<?php

namespace App\Modules\Tenants\Presenters;

use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Tenants\Data\TenantDetailData;

final class TenantDetailTabPresenter
{
    /** @return array<int, string> */
    public function present(TenantDetailData $data): array
    {
        $tabs = ['overview'];

        if (PortfolioModules::enabledForUser($data->actor, 'leases')) {
            $tabs[] = 'rental';
        }

        if (PortfolioModules::enabledForUser($data->actor, 'payments')) {
            $tabs[] = 'payments';
        }

        if (PortfolioModules::enabledForUser($data->actor, 'maintenance')) {
            $tabs[] = 'service';
        }

        if (PortfolioModules::enabledForUser($data->actor, 'documents')) {
            $tabs[] = 'documents';
        }

        $tabs[] = 'history';

        return $tabs;
    }
}
