<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\MaintenanceWorkOrder;
use Illuminate\Database\Eloquent\Builder;

final class MaintenanceWorkOrderInsightsQuery
{
    /**
     * @param  Builder<MaintenanceWorkOrder>  $query
     * @return array<string, int>
     */
    public function get(Builder $query): array
    {
        $row = (clone $query)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status IN ('scheduled', 'in_progress') THEN 1 ELSE 0 END) as active")
            ->selectRaw("SUM(CASE WHEN status = 'draft' AND scheduled_at IS NULL THEN 1 ELSE 0 END) as unscheduled")
            ->selectRaw("SUM(CASE WHEN status IN ('scheduled', 'in_progress') AND scheduled_at < ? THEN 1 ELSE 0 END) as overdue", [now()])
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN assigned_to_user_id IS NULL AND status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END) as unassigned")
            ->selectRaw("SUM(CASE WHEN tenant_access_required = 1 AND status IN ('scheduled', 'in_progress') THEN 1 ELSE 0 END) as tenant_access")
            ->first();

        return [
            'total' => (int) ($row?->getAttribute('total') ?? 0),
            'active' => (int) ($row?->getAttribute('active') ?? 0),
            'unscheduled' => (int) ($row?->getAttribute('unscheduled') ?? 0),
            'overdue' => (int) ($row?->getAttribute('overdue') ?? 0),
            'completed' => (int) ($row?->getAttribute('completed') ?? 0),
            'unassigned' => (int) ($row?->getAttribute('unassigned') ?? 0),
            'tenant_access' => (int) ($row?->getAttribute('tenant_access') ?? 0),
        ];
    }
}
