<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;

class OperationsOccupancyQuery
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    /** @return array<string, int> */
    public function forUser(User $user): array
    {
        return $this->portfolios
            ->apply(Asset::query(), $user)
            ->where('rentable', true)
            ->selectRaw('occupancy_status, COUNT(*) as total')
            ->groupBy('occupancy_status')
            ->pluck('total', 'occupancy_status')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }
}
