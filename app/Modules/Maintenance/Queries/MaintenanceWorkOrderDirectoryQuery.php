<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceWorkOrderOptions;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class MaintenanceWorkOrderDirectoryQuery
{
    private const SCHEDULE_FILTERS = ['all', 'unscheduled', 'overdue', 'today', 'upcoming'];

    private const ACCESS_FILTERS = ['all', 'required', 'not_required'];

    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly PropertyScope $properties,
        private readonly AssignedPropertyScope $assignments,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        $filters = $this->tables->filters($request, [
            'status' => 'all',
            'vendor_id' => 'all',
            'assigned_to_user_id' => 'all',
            'property_id' => 'all',
            'schedule' => 'all',
            'tenant_access' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);
        $filters['status'] = $this->allowed(
            (string) $filters['status'],
            ['all', 'active', ...MaintenanceWorkOrderOptions::STATUSES],
        );
        $filters['schedule'] = $this->allowed(
            (string) $filters['schedule'],
            self::SCHEDULE_FILTERS,
        );
        $filters['tenant_access'] = $this->allowed(
            (string) $filters['tenant_access'],
            self::ACCESS_FILTERS,
        );

        return $filters;
    }

    /** @return Builder<MaintenanceWorkOrder> */
    public function base(User $actor): Builder
    {
        $this->access->ensureManager($actor);
        $query = $this->portfolios->apply(MaintenanceWorkOrder::query(), $actor);

        return $this->assignments->restricts($actor)
            ? $query->whereIn(
                'maintenance_request_id',
                MaintenanceRequest::query()
                    ->whereIn('asset_id', $this->assignments->assetIds($actor) ?? [])
                    ->select('id'),
            )
            : $query;
    }

    /**
     * @param  Builder<MaintenanceWorkOrder>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyScope(Builder $query, array $filters, User $actor): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');
        $assetIds = $this->properties->assetIds(
            $actor,
            $filters['portfolio_id'],
            $filters['property_id'],
        );

        if ($assetIds !== null) {
            $query->whereHas(
                'maintenanceRequest',
                fn (Builder $requests) => $requests->whereIn('asset_id', $assetIds),
            );
        }
    }

    /**
     * @param  Builder<MaintenanceWorkOrder>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['status'] === 'active') {
            $query->whereIn('status', ['scheduled', 'in_progress']);
        } else {
            $this->tables->exact($query, $filters, 'status');
        }
        $this->tables->exact($query, $filters, 'vendor_id');
        $this->tables->exact($query, $filters, 'assigned_to_user_id');
        $this->tables->dateRange($query, $filters, 'scheduled_at');
        $this->applySchedule($query, (string) $filters['schedule']);

        if ($filters['tenant_access'] !== 'all') {
            $query->where(
                'tenant_access_required',
                $filters['tenant_access'] === 'required',
            );
        }

        $this->tables->search($query, (string) $filters['search'], [
            'reference_code',
            'vendor_name',
            'vendor_phone',
            'scope',
            fn (Builder $orders, string $search, string $like) => $orders->orWhereHas(
                'maintenanceRequest',
                fn (Builder $requests) => $requests
                    ->where('title', 'like', $like)
                    ->orWhereHas('asset', fn (Builder $assets) => $assets
                        ->where('title_en', 'like', $like)
                        ->orWhere('title_ar', 'like', $like)
                        ->orWhere('code', 'like', $like))
                    ->orWhereHas('tenantProfile.user', fn (Builder $users) => $users
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)),
            ),
            fn (Builder $orders, string $search, string $like) => $orders->orWhereHas(
                'assignedTo',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like),
            ),
        ]);
    }

    /**
     * @param  Builder<MaintenanceWorkOrder>  $query
     * @return Builder<MaintenanceWorkOrder>
     */
    public function listing(Builder $query): Builder
    {
        return $query
            ->select([
                'id', 'portfolio_id', 'maintenance_request_id', 'vendor_id',
                'created_by_user_id', 'assigned_to_user_id', 'reference_code',
                'vendor_name', 'status', 'scheduled_at', 'completed_at',
                'estimated_amount', 'final_amount', 'currency', 'scope',
                'tenant_access_required', 'created_at',
            ])
            ->with([
                'maintenanceRequest:id,portfolio_id,asset_id,tenant_profile_id,title,category,priority,status',
                'maintenanceRequest.asset:id,portfolio_id,title_en,title_ar,code',
                'maintenanceRequest.tenantProfile:id,portfolio_id,user_id',
                'maintenanceRequest.tenantProfile.user:id,name',
                'vendor:id,name',
                'assignedTo:id,name',
            ]);
    }

    /**
     * @param  Builder<MaintenanceWorkOrder>  $query
     * @return array<int, array{id:int,name:string}>
     */
    public function vendorOptions(Builder $query): array
    {
        return MaintenanceVendor::query()
            ->whereIn('id', (clone $query)->whereNotNull('vendor_id')->select('vendor_id'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (MaintenanceVendor $vendor): array => [
                'id' => $vendor->id,
                'name' => $vendor->name,
            ])
            ->all();
    }

    /**
     * @param  Builder<MaintenanceWorkOrder>  $query
     * @return array<int, array{id:int,name:string}>
     */
    public function assigneeOptions(Builder $query): array
    {
        return User::query()
            ->whereIn(
                'id',
                (clone $query)->whereNotNull('assigned_to_user_id')->select('assigned_to_user_id'),
            )
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])
            ->all();
    }

    /** @param Builder<MaintenanceWorkOrder> $query */
    private function applySchedule(Builder $query, string $schedule): void
    {
        match ($schedule) {
            'unscheduled' => $query
                ->where('status', 'draft')
                ->whereNull('scheduled_at'),
            'overdue' => $query
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->where('scheduled_at', '<', now()),
            'today' => $query
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->whereDate('scheduled_at', today()),
            'upcoming' => $query
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->where('scheduled_at', '>=', now()),
            default => null,
        };
    }

    /** @param list<string> $allowed */
    private function allowed(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : 'all';
    }
}
