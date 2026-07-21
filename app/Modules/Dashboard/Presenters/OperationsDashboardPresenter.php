<?php

namespace App\Modules\Dashboard\Presenters;

use App\Models\User;
use App\Modules\Dashboard\Queries\DashboardPropertyMapQuery;
use App\Modules\Dashboard\Queries\OperationsActivityQuery;
use App\Modules\Dashboard\Queries\OperationsLeaseQuery;
use App\Modules\Dashboard\Queries\OperationsOccupancyQuery;
use App\Modules\Dashboard\Queries\OperationsStatsQuery;
use App\Modules\Dashboard\Queries\PlatformStatusQuery;

class OperationsDashboardPresenter
{
    public function __construct(
        private readonly OperationsStatsQuery $stats,
        private readonly OperationsOccupancyQuery $occupancy,
        private readonly OperationsLeaseQuery $leases,
        private readonly OperationsActivityQuery $activity,
        private readonly DashboardPropertyMapQuery $propertyMap,
        private readonly PlatformStatusQuery $platformStatus,
        private readonly SetupChecklistPresenter $checklist,
        private readonly DashboardActionPresenter $actions,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $user): array
    {
        $stats = $this->stats->forUser($user);
        $checklist = $this->checklist->present($user, $stats);
        $propertyMap = $this->propertyMap->forUser($user);

        return [
            'mode' => $user->hasRole('superadmin') ? 'superadmin' : 'portfolio',
            'stats' => $stats,
            'nextActions' => $this->actions->operations($checklist, $stats, $propertyMap['summary']),
            'charts' => ['occupancy' => $this->occupancy->forUser($user)],
            'setupChecklist' => $checklist,
            'propertyMap' => $propertyMap,
            ...$this->leases->forUser($user),
            ...$this->activity->forUser($user),
            'cmsStatus' => $this->platformStatus->forUser($user),
        ];
    }
}
