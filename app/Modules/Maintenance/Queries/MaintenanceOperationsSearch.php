<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;

final class MaintenanceOperationsSearch extends ModuleSearchSource
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly AssignedPropertyScope $assignments,
        private readonly TableQuery $tables,
        private readonly SearchResultPresenter $results,
    ) {}

    public function results(User $actor, string $query): array
    {
        if (! $this->supports($actor)) {
            return [];
        }

        $vendors = $this->portfolios->apply(MaintenanceVendor::query(), $actor);
        $this->tables->search($vendors, $query, [
            'name',
            'contact_name',
            'phone',
            'email',
        ]);
        $workOrders = $this->workOrders($actor)->with('maintenanceRequest.asset');
        $this->tables->search($workOrders, $query, [
            'reference_code',
            'vendor_name',
            'scope',
            fn (Builder $orders, string $term, string $like) => $orders->orWhereHas(
                'maintenanceRequest',
                fn (Builder $requests) => $requests->where('title', 'like', $like),
            ),
        ]);

        return [
            ...$workOrders->latest()->limit(4)->get()->map(
                fn (MaintenanceWorkOrder $workOrder): array => $this->results->result(
                    trans('app.nav.work_orders'),
                    $workOrder->reference_code,
                    $workOrder->maintenanceRequest?->title ?: $workOrder->vendor_name,
                    $this->results->status($workOrder->status),
                    route('maintenance-work-orders.show', $workOrder),
                ),
            )->all(),
            ...$vendors->orderBy('name')->limit(3)->get()->map(
                fn (MaintenanceVendor $vendor): array => $this->results->result(
                    trans('app.nav.maintenance_vendors'),
                    $vendor->name,
                    $vendor->contact_name ?: $vendor->phone,
                    $this->results->status($vendor->status),
                    route('maintenance-vendors.show', $vendor),
                ),
            )->all(),
        ];
    }

    public function directUrl(User $actor, string $query): ?string
    {
        if (! $this->supports($actor)) {
            return null;
        }

        $workOrder = $this->workOrders($actor)
            ->where('reference_code', $query)
            ->first();

        return $workOrder ? route('maintenance-work-orders.show', $workOrder) : null;
    }

    private function supports(User $actor): bool
    {
        return $this->isManager($actor)
            && $this->moduleEnabled($actor, 'maintenance');
    }

    /** @return Builder<MaintenanceWorkOrder> */
    private function workOrders(User $actor): Builder
    {
        $query = $this->portfolios->apply(MaintenanceWorkOrder::query(), $actor);

        if ($this->assignments->restricts($actor)) {
            $query->whereIn(
                'maintenance_request_id',
                $this->assignments
                    ->maintenance(MaintenanceRequest::query(), $actor)
                    ->select('id'),
            );
        }

        return $query;
    }
}
