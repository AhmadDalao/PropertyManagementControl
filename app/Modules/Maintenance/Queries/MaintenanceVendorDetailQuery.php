<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Modules\Maintenance\Data\MaintenanceVendorDetailData;
use App\Modules\Maintenance\Support\MaintenanceVendorAccess;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use Illuminate\Database\Eloquent\Builder;

final class MaintenanceVendorDetailQuery
{
    private const OPEN_STATUSES = ['draft', 'scheduled', 'in_progress'];

    public function __construct(
        private readonly MaintenanceVendorAccess $access,
        private readonly AssignedPropertyScope $assignments,
    ) {}

    public function get(MaintenanceVendor $vendor, User $actor): MaintenanceVendorDetailData
    {
        $this->access->ensureCanAccess($actor, $vendor);
        $vendor->loadMissing('portfolio');
        $base = $this->base($vendor, $actor);
        $summary = $this->summary($base);
        $open = $this->context((clone $base)->whereIn('status', self::OPEN_STATUSES))
            ->orderByRaw(
                "CASE WHEN scheduled_at IS NOT NULL AND scheduled_at < ? THEN 0 WHEN status = 'in_progress' THEN 1 WHEN status = 'scheduled' THEN 2 ELSE 3 END",
                [now()->toDateTimeString()],
            )
            ->orderByRaw('CASE WHEN scheduled_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('scheduled_at')
            ->latest('id')
            ->limit(8)
            ->get();
        $history = $this->context((clone $base)->whereIn('status', ['completed', 'cancelled']))
            ->latest('completed_at')
            ->latest('id')
            ->limit(8)
            ->get();

        return new MaintenanceVendorDetailData(
            vendor: $vendor,
            actor: $actor,
            openWorkOrders: $open,
            historyWorkOrders: $history,
            nextWorkOrder: $open->first(),
            counts: $this->counts($base, $summary),
            financial: [
                'active_quoted' => (float) ($summary?->getAttribute('active_quoted') ?? 0),
                'completed_quoted' => (float) ($summary?->getAttribute('completed_quoted') ?? 0),
                'completed_final' => (float) ($summary?->getAttribute('completed_final') ?? 0),
                'currency' => strtoupper((string) ($vendor->portfolio?->default_currency ?: 'SAR')),
            ],
            statusLabel: trans("app.status.{$vendor->status}"),
            statusTone: $vendor->status === 'active' ? 'teal' : 'muted',
            categoryLabel: trans("app.status.{$vendor->service_category}"),
        );
    }

    /** @return Builder<MaintenanceWorkOrder> */
    private function base(MaintenanceVendor $vendor, User $actor): Builder
    {
        return $this->assignments->workOrders(
            MaintenanceWorkOrder::query()
                ->where('portfolio_id', $vendor->portfolio_id)
                ->where('vendor_id', $vendor->id),
            $actor,
        );
    }

    /** @param Builder<MaintenanceWorkOrder> $query */
    private function summary(Builder $query): ?MaintenanceWorkOrder
    {
        return (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status IN ('draft','scheduled','in_progress') THEN 1 ELSE 0 END) as open_count")
            ->selectRaw("SUM(CASE WHEN status IN ('scheduled','in_progress') THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count")
            ->selectRaw("SUM(CASE WHEN status IN ('scheduled','in_progress') THEN estimated_amount ELSE 0 END) as active_quoted")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN estimated_amount ELSE 0 END) as completed_quoted")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as completed_final")
            ->first();
    }

    /**
     * @param  Builder<MaintenanceWorkOrder>  $base
     * @return array{total:int,open:int,active:int,draft:int,completed:int,cancelled:int,overdue:int,today:int,upcoming:int,unscheduled:int,properties:int}
     */
    private function counts(Builder $base, ?MaintenanceWorkOrder $summary): array
    {
        $active = fn (): Builder => (clone $base)->whereIn('status', ['scheduled', 'in_progress']);

        return [
            'total' => (int) ($summary?->getAttribute('total') ?? 0),
            'open' => (int) ($summary?->getAttribute('open_count') ?? 0),
            'active' => (int) ($summary?->getAttribute('active_count') ?? 0),
            'draft' => (int) ($summary?->getAttribute('draft_count') ?? 0),
            'completed' => (int) ($summary?->getAttribute('completed_count') ?? 0),
            'cancelled' => (int) ($summary?->getAttribute('cancelled_count') ?? 0),
            'overdue' => $active()->where('scheduled_at', '<', now())->count(),
            'today' => $active()->whereBetween('scheduled_at', [now(), today()->endOfDay()])->count(),
            'upcoming' => $active()->where('scheduled_at', '>', today()->endOfDay())->count(),
            'unscheduled' => (clone $base)->whereIn('status', self::OPEN_STATUSES)->whereNull('scheduled_at')->count(),
            'properties' => MaintenanceRequest::query()
                ->whereIn('id', (clone $base)->select('maintenance_request_id'))
                ->whereNotNull('asset_id')
                ->distinct('asset_id')
                ->count('asset_id'),
        ];
    }

    /**
     * @param  Builder<MaintenanceWorkOrder>  $query
     * @return Builder<MaintenanceWorkOrder>
     */
    private function context(Builder $query): Builder
    {
        return $query->with([
            'maintenanceRequest:id,portfolio_id,asset_id,tenant_profile_id,title,priority,status',
            'maintenanceRequest.asset:id,portfolio_id,title_en,title_ar,code',
            'maintenanceRequest.tenantProfile:id,portfolio_id,user_id',
            'maintenanceRequest.tenantProfile.user:id,name',
            'assignedTo:id,name',
        ]);
    }
}
