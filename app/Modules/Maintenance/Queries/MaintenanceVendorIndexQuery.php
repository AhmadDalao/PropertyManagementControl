<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceOptions;
use App\Modules\Maintenance\Support\MaintenanceVendorAccess;
use App\Modules\Maintenance\Support\MaintenanceVendorOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class MaintenanceVendorIndexQuery
{
    public function __construct(
        private readonly MaintenanceVendorAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $this->access->ensureManager($actor);
        $filters = $this->tables->filters($request, [
            'status' => 'all',
            'service_category' => 'all',
        ]);
        $filters['status'] = in_array(
            $filters['status'],
            ['all', ...MaintenanceVendorOptions::STATUSES],
            true,
        ) ? $filters['status'] : 'all';
        $filters['service_category'] = in_array(
            $filters['service_category'],
            ['all', ...MaintenanceOptions::CATEGORIES],
            true,
        ) ? $filters['service_category'] : 'all';

        $base = $this->portfolios->apply(
            MaintenanceVendor::query(),
            $actor,
        );
        $this->tables->exact($base, $filters, 'portfolio_id');
        $summary = clone $base;
        $listing = clone $base;
        $this->tables->exact($listing, $filters, 'status');
        $this->tables->exact($listing, $filters, 'service_category');
        $this->tables->search($listing, (string) $filters['search'], [
            'name',
            'contact_name',
            'phone',
            'email',
            'notes',
        ]);
        $listing
            ->select([
                'id',
                'portfolio_id',
                'name',
                'contact_name',
                'phone',
                'email',
                'service_category',
                'status',
                'created_at',
            ])
            ->with('portfolio:id,name_en,name_ar')
            ->withCount([
                'workOrders',
                'workOrders as active_work_orders_count' => fn (Builder $query) => $query
                    ->whereIn('status', ['scheduled', 'in_progress']),
            ]);

        $counts = $this->tables->statusCounts(
            $summary,
            MaintenanceVendorOptions::STATUSES,
            $filters,
        );

        return [
            'vendors' => $this->tables
                ->paginate($listing, $filters, ['created_at', 'name', 'status', 'service_category'], 'name')
                ->through(fn (MaintenanceVendor $vendor): array => $this->row($vendor)),
            'vendorInsights' => $this->insights($summary),
            'filters' => $filters,
            'counts' => collect($counts)->map(function (array $count): array {
                $status = (string) data_get($count, 'filter.status', 'all');
                $count['label'] = $status === 'all'
                    ? trans('app.maintenance_vendors.all')
                    : trans("app.status.{$status}");

                return $count;
            })->all(),
            'portfolioOptions' => $this->portfolios->options($actor),
            'categoryOptions' => MaintenanceOptions::CATEGORIES,
            'statusOptions' => MaintenanceVendorOptions::STATUSES,
        ];
    }

    /** @return array<string, mixed> */
    private function row(MaintenanceVendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'portfolio_id' => $vendor->portfolio_id,
            'name' => $vendor->name,
            'contact_name' => $vendor->contact_name,
            'phone' => $vendor->phone,
            'email' => $vendor->email,
            'service_category' => $vendor->service_category,
            'status' => $vendor->status,
            'work_orders_count' => (int) $vendor->work_orders_count,
            'active_work_orders_count' => (int) $vendor->active_work_orders_count,
            'portfolio' => $vendor->portfolio ? [
                'id' => $vendor->portfolio->id,
                'name_en' => $vendor->portfolio->name_en,
                'name_ar' => $vendor->portfolio->name_ar,
            ] : null,
        ];
    }

    /**
     * @param  Builder<MaintenanceVendor>  $query
     * @return array<string, int>
     */
    private function insights(Builder $query): array
    {
        $row = (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active")
            ->selectRaw("SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive")
            ->first();
        $activeJobs = MaintenanceWorkOrder::query()
            ->whereIn('vendor_id', (clone $query)->select('id'))
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->count();

        return [
            'total' => (int) ($row?->getAttribute('total') ?? 0),
            'active' => (int) ($row?->getAttribute('active') ?? 0),
            'inactive' => (int) ($row?->getAttribute('inactive') ?? 0),
            'active_work_orders' => (int) $activeJobs,
        ];
    }
}
