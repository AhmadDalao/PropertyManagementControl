<?php

namespace App\Modules\CompanyControl\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Dashboard\Queries\DashboardPropertyContextQuery;
use App\Modules\Dashboard\Queries\PropertyPerformanceDatasetQuery;
use App\Modules\PortfolioControl\Support\PortfolioControlCurrencyPositions;
use Illuminate\Support\Collection;

final readonly class CompanyPortfolioOperationsQuery
{
    public function __construct(
        private DashboardPropertyContextQuery $context,
        private PropertyPerformanceDatasetQuery $performance,
        private PortfolioControlCurrencyPositions $positions,
    ) {}

    /** @return Collection<int|string, array<string, mixed>> */
    public function get(User $actor): Collection
    {
        $activeRootIds = Asset::query()
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->pluck('id');

        return collect($this->performance->forUser(
            $actor,
            $this->context->forUser($actor, null),
        ))
            ->whereIn('id', $activeRootIds)
            ->groupBy('portfolio_id')
            ->map(fn (Collection $properties): array => $this->aggregate($properties));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $properties
     * @return array<string, mixed>
     */
    private function aggregate(Collection $properties): array
    {
        $currencies = [];

        foreach ($properties as $property) {
            foreach ($this->positions->from($property) as $position) {
                $currency = $position['currency'];
                $currencies[$currency] ??= $this->positions->empty($currency);

                foreach (['scheduled_due', 'scheduled_paid', 'arrears', 'collected', 'expenses'] as $field) {
                    $currencies[$currency][$field] += $position[$field];
                }
            }
        }

        foreach ($currencies as &$position) {
            $position['collection_rate'] = $position['scheduled_due'] > 0
                ? round(min(100, ($position['scheduled_paid'] / $position['scheduled_due']) * 100), 1)
                : 0.0;
            $position['net'] = $position['collected'] - $position['expenses'];
        }
        unset($position);
        ksort($currencies);
        $rentable = (int) $properties->sum('rentable_units');
        $occupied = (int) $properties->sum('occupied_units');

        return [
            'properties' => $properties->count(),
            'risk_properties' => $properties->where('attention', 'risk')->count(),
            'watch_properties' => $properties->where('attention', 'watch')->count(),
            'rentable_units' => $rentable,
            'occupied_units' => $occupied,
            'occupancy_rate' => $rentable > 0 ? round(($occupied / $rentable) * 100, 1) : 0.0,
            'active_leases' => (int) $properties->sum('active_leases'),
            'expiring_leases' => (int) $properties->sum('expiring_leases'),
            'open_requests' => (int) $properties->sum('open_requests'),
            'currency_totals' => array_values($currencies),
        ];
    }
}
