<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\Maintenance\Presenters\MaintenanceTableRowPresenter;
use App\Modules\Maintenance\Support\MaintenanceOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MaintenanceIndexQuery
{
    public function __construct(
        private readonly MaintenanceDirectoryQuery $directory,
        private readonly MaintenanceInsightsQuery $insights,
        private readonly MaintenanceTableRowPresenter $rows,
        private readonly PortfolioScope $portfolios,
        private readonly PropertyScope $properties,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->directory->filters($request);
        $tenantMode = $actor->hasRole('tenant');
        $baseQuery = $tenantMode
            ? $this->directory->tenantBase($actor)
            : $this->directory->managerBase($actor);
        $summaryQuery = clone $baseQuery;

        if (! $tenantMode) {
            $this->directory->applyManagerScope($summaryQuery, $filters, $actor);
        }

        $requests = $this->directory->listing(clone $baseQuery, ! $tenantMode);

        if ($tenantMode) {
            $this->directory->applyTenantFilters($requests, $filters);
        } else {
            $this->directory->applyManagerFilters($requests, $filters, $actor);
        }

        return [
            'mode' => $tenantMode ? 'tenant' : 'manager',
            'requests' => $this->tables->paginate($requests, $filters, [
                'created_at',
                'requested_at',
                'status',
                'priority',
                'category',
            ])->through(
                fn (MaintenanceRequest $item): array => $this->rows->present($item, ! $tenantMode),
            ),
            'maintenanceInsights' => $this->insights->get($summaryQuery, ! $tenantMode),
            'filters' => $filters,
            'counts' => $this->tables->statusCounts(
                $summaryQuery,
                MaintenanceOptions::STATUSES,
                $filters,
            ),
            'categoryOptions' => MaintenanceOptions::CATEGORIES,
            'priorityOptions' => MaintenanceOptions::PRIORITIES,
            'statusOptions' => MaintenanceOptions::STATUSES,
            'portfolioOptions' => $tenantMode ? [] : $this->portfolios->options($actor),
            'propertyOptions' => $tenantMode ? [] : $this->properties->options($actor),
        ];
    }

    /** @return Builder<MaintenanceRequest> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->directory->filters($request);
        $requests = $this->directory->managerBase($actor)
            ->with(['asset', 'tenantProfile.user', 'assignedTo']);
        $this->directory->applyManagerFilters($requests, $filters, $actor);

        return $requests;
    }
}
