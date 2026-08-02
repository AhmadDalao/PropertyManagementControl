<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\Maintenance\Presenters\MaintenanceWorkOrderRowPresenter;
use App\Modules\Maintenance\Support\MaintenanceWorkOrderOptions;
use App\Modules\Shared\LocalizedStatusCounts;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class MaintenanceWorkOrderIndexQuery
{
    public function __construct(
        private readonly MaintenanceWorkOrderDirectoryQuery $directory,
        private readonly MaintenanceWorkOrderInsightsQuery $insights,
        private readonly MaintenanceWorkOrderRowPresenter $rows,
        private readonly PortfolioScope $portfolios,
        private readonly PropertyScope $properties,
        private readonly TableQuery $tables,
        private readonly LocalizedStatusCounts $statuses,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->directory->filters($request);
        $scoped = $this->directory->base($actor);
        $this->directory->applyScope($scoped, $filters, $actor);
        $listing = $this->directory->listing(clone $scoped);
        $this->directory->applyFilters($listing, $filters);

        return [
            'workOrders' => $this->tables
                ->paginate($listing, $filters, [
                    'created_at', 'reference_code', 'status', 'scheduled_at',
                    'estimated_amount', 'final_amount',
                ])
                ->through(fn (MaintenanceWorkOrder $item): array => $this->rows->present($item)),
            'workOrderInsights' => $this->insights->get(clone $scoped),
            'filters' => $filters,
            'counts' => $this->statuses->present(
                $this->tables->statusCounts(
                    clone $scoped,
                    MaintenanceWorkOrderOptions::STATUSES,
                    $filters,
                ),
                'app.work_orders.all',
            ),
            'portfolioOptions' => $this->portfolios->options($actor),
            'propertyOptions' => $this->properties->options($actor),
            'vendorOptions' => $this->directory->vendorOptions(clone $scoped),
            'assigneeOptions' => $this->directory->assigneeOptions(clone $scoped),
            'statusOptions' => ['active', ...MaintenanceWorkOrderOptions::STATUSES],
            'scheduleOptions' => ['unscheduled', 'overdue', 'today', 'upcoming'],
            'tenantAccessOptions' => ['required', 'not_required'],
        ];
    }

    /** @return Builder<MaintenanceWorkOrder> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->directory->filters($request);
        $query = $this->directory->listing($this->directory->base($actor));
        $this->directory->applyScope($query, $filters, $actor);
        $this->directory->applyFilters($query, $filters);

        return $query;
    }
}
