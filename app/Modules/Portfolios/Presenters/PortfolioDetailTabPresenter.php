<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Data\PortfolioDetailData;

final class PortfolioDetailTabPresenter
{
    /** @return list<string> */
    public function present(PortfolioDetailData $data, User $actor): array
    {
        $tabs = ['overview'];
        $visible = fn (string $module): bool => $actor->hasRole('superadmin')
            || ($data->settings[$module] ?? true);

        if ($visible('assets')) {
            $tabs[] = 'properties';
        }

        if ($visible('users')) {
            $tabs[] = 'people';
        }

        if ($visible('leases') || $visible('maintenance')) {
            $tabs[] = 'operations';
        }

        if (collect(['assets', 'payments', 'expenses', 'reports'])->contains($visible)) {
            $tabs[] = 'financial';
        }

        if ($visible('documents')) {
            $tabs[] = 'documents';
        }

        return [...$tabs, 'history'];
    }
}
