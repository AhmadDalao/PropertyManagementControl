<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioModules;

final class UserDetailTabPresenter
{
    /** @return list<string> */
    public function present(User $actor): array
    {
        $tabs = ['overview', 'access'];

        if (PortfolioModules::enabledForUser($actor, 'assets')) {
            $tabs[] = 'properties';
        }

        if (PortfolioModules::enabledForUser($actor, 'maintenance')) {
            $tabs[] = 'workload';
        }

        if (PortfolioModules::enabledForUser($actor, 'documents')) {
            $tabs[] = 'documents';
        }

        $tabs[] = 'history';

        return $tabs;
    }
}
