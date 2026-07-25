<?php

namespace App\Modules\Dashboard\Presenters;

use App\Models\User;
use App\Modules\Dashboard\Queries\DashboardPropertyMapQuery;
use App\Modules\Dashboard\Queries\LaunchReadinessSummaryQuery;
use App\Modules\Dashboard\Queries\OperationsActivityQuery;
use App\Modules\Dashboard\Queries\OperationsCollectionQuery;
use App\Modules\Dashboard\Queries\OperationsFinancialQuery;
use App\Modules\Dashboard\Queries\OperationsLeaseQuery;
use App\Modules\Dashboard\Queries\OperationsOccupancyQuery;
use App\Modules\Dashboard\Queries\OperationsPropertyPerformanceQuery;
use App\Modules\Dashboard\Queries\OperationsStatsQuery;
use App\Modules\Dashboard\Queries\PlatformStatusQuery;

class OperationsDashboardPresenter
{
    public function __construct(
        private readonly OperationsStatsQuery $stats,
        private readonly OperationsOccupancyQuery $occupancy,
        private readonly OperationsLeaseQuery $leases,
        private readonly OperationsCollectionQuery $collections,
        private readonly OperationsFinancialQuery $financial,
        private readonly OperationsPropertyPerformanceQuery $properties,
        private readonly OperationsActivityQuery $activity,
        private readonly DashboardPropertyMapQuery $propertyMap,
        private readonly PlatformStatusQuery $platformStatus,
        private readonly LaunchReadinessSummaryQuery $launchReadiness,
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
            'financial' => $this->financial->forUser($user),
            'nextActions' => $this->actions->operations($checklist, $stats, $propertyMap['summary']),
            'charts' => ['occupancy' => $this->occupancy->forUser($user)],
            'setupChecklist' => $checklist,
            'propertyMap' => $propertyMap,
            'propertyPerformance' => $this->properties->forUser($user),
            'collectionQueue' => $this->collections->forUser($user),
            ...$this->leases->forUser($user),
            ...$this->activity->forUser($user),
            'cmsStatus' => $this->platformStatus->forUser($user),
            'readinessStatus' => $this->launchReadiness->forUser($user),
        ];
    }
}
