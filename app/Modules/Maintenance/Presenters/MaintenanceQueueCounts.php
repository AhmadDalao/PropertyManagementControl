<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceRequest;
use App\Modules\Maintenance\Support\MaintenanceOptions;
use App\Modules\Shared\LocalizedStatusCounts;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;

final readonly class MaintenanceQueueCounts
{
    public function __construct(
        private TableQuery $tables,
        private LocalizedStatusCounts $statuses,
    ) {}

    /**
     * @param  Builder<MaintenanceRequest>  $query
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function present(Builder $query, array $filters): array
    {
        $confirmation = (string) $filters['confirmation'];
        $counts = $this->statuses->present(
            $this->tables->statusCounts($query, MaintenanceOptions::STATUSES, $filters),
            'app.maintenance.all',
        );

        $counts = collect($counts)->map(function (array $count) use ($confirmation): array {
            $count['filter']['confirmation'] = 'all';
            $count['active'] = $confirmation === 'all' && $count['active'];

            return $count;
        })->all();

        $counts[] = [
            'label' => trans('app.maintenance.pending_confirmation'),
            'value' => $this->pending($query),
            'filter' => ['status' => 'all', 'confirmation' => 'pending'],
            'active' => $confirmation === 'pending',
        ];

        return $counts;
    }

    /** @param Builder<MaintenanceRequest> $query */
    private function pending(Builder $query): int
    {
        return (clone $query)
            ->where('status', 'resolved')
            ->whereNull('tenant_confirmed_at')
            ->count();
    }
}
