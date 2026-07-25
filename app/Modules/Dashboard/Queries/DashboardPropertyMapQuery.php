<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\PropertyMapPresenter;
use App\Modules\Dashboard\Support\DashboardPropertyContext;
use App\Modules\Shared\PortfolioScope;

class DashboardPropertyMapQuery
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly PropertyMapPresenter $propertyMap,
    ) {}

    /** @return array<string, mixed> */
    public function forUser(User $user, DashboardPropertyContext $context): array
    {
        return $this->propertyMap->forQuery(
            $context->assets(
                $this->portfolios->apply(Asset::query(), $user),
            ),
        );
    }
}
