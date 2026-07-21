<?php

namespace App\Modules\Portfolios\Support;

use App\Models\Portfolio;
use App\Models\User;
use App\Support\PortfolioModules;

final class PortfolioOptions
{
    /** @var array<int, string> */
    public const STATUSES = ['active', 'inactive', 'archived'];

    /** @var array<int, string> */
    public const CREATION_STATUSES = ['active', 'inactive'];

    /** @return array<int, string> */
    public static function moduleKeys(): array
    {
        return array_keys(PortfolioModules::defaults());
    }

    /** @return array<int, string> */
    public static function updateStatuses(User $actor, Portfolio $portfolio): array
    {
        if ($actor->hasRole('superadmin')) {
            return self::STATUSES;
        }

        return $portfolio->status === 'archived'
            ? ['archived']
            : ['active', 'inactive'];
    }

    public static function moduleField(string $module): string
    {
        return "module_{$module}";
    }
}
