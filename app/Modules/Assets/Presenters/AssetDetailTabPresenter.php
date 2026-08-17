<?php

namespace App\Modules\Assets\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioModules;

final class AssetDetailTabPresenter
{
    /** @return list<string> */
    public function present(User $actor): array
    {
        $tabs = ['overview', 'structure'];

        if ($this->enabled($actor, 'leases')) {
            $tabs[] = 'leasing';
        }

        if ($this->enabled($actor, 'reports') || $this->enabled($actor, 'expenses')) {
            $tabs[] = 'financial';
        }

        if ($this->enabled($actor, 'maintenance')) {
            $tabs[] = 'service';
        }

        if ($this->enabled($actor, 'documents')) {
            $tabs[] = 'documents';
        }

        return [...$tabs, 'history'];
    }

    private function enabled(User $actor, string $module): bool
    {
        return PortfolioModules::enabledForUser($actor, $module);
    }
}
