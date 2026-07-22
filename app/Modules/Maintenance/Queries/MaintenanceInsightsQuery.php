<?php

namespace App\Modules\Maintenance\Queries;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceInsightsQuery
{
    /**
     * @param  Builder<MaintenanceRequest>  $baseQuery
     * @return array<string, int|float>
     */
    public function get(Builder $baseQuery, bool $includeFinancials): array
    {
        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count")
            ->selectRaw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count")
            ->selectRaw("SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count")
            ->selectRaw("SUM(CASE WHEN status IN ('open', 'in_progress') AND priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count")
            ->selectRaw(
                "SUM(CASE WHEN status IN ('open', 'in_progress') AND due_at IS NOT NULL AND due_at < ? THEN 1 ELSE 0 END) as overdue_count",
                [now()],
            )
            ->selectRaw("SUM(CASE WHEN status IN ('open', 'in_progress') AND assigned_to_user_id IS NULL THEN 1 ELSE 0 END) as unassigned_count")
            ->first();

        return [
            'total' => (int) ($summary?->getAttribute('total') ?? 0),
            'open' => (int) ($summary?->getAttribute('open_count') ?? 0),
            'in_progress' => (int) ($summary?->getAttribute('in_progress_count') ?? 0),
            'resolved' => (int) ($summary?->getAttribute('resolved_count') ?? 0),
            'cancelled' => (int) ($summary?->getAttribute('cancelled_count') ?? 0),
            'urgent' => (int) ($summary?->getAttribute('urgent_count') ?? 0),
            'overdue' => (int) ($summary?->getAttribute('overdue_count') ?? 0),
            'unassigned' => (int) ($summary?->getAttribute('unassigned_count') ?? 0),
            'posted_expenses' => $includeFinancials
                ? $this->postedExpenses($baseQuery)
                : 0,
        ];
    }

    /** @param Builder<MaintenanceRequest> $baseQuery */
    private function postedExpenses(Builder $baseQuery): float
    {
        return (float) ExpenseEntry::query()
            ->whereIn('maintenance_request_id', (clone $baseQuery)->select('id'))
            ->where('status', 'posted')
            ->sum('amount');
    }
}
