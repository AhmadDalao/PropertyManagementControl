<?php

namespace App\Modules\Search\Support;

use App\Models\User;
use App\Modules\Search\Contracts\SearchSource;
use App\Support\PortfolioModules;

abstract class ModuleSearchSource implements SearchSource
{
    public function directUrl(User $actor, string $query): ?string
    {
        return null;
    }

    protected function isManager(User $actor): bool
    {
        return $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']);
    }

    protected function moduleEnabled(User $actor, string $module): bool
    {
        return PortfolioModules::enabledForUser($actor, $module);
    }
}
