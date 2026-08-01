<?php

namespace App\Modules\Reports\Presenters;

use App\Modules\Reports\Data\LeaseReportSnapshot;
use App\Modules\Reports\Data\PortfolioReportData;

final class ReportComparisonPresenter
{
    public function __construct(
        private readonly ReportCurrencySummaryPresenter $currencies,
    ) {}

    /**
     * @param  array{date_from:string,date_to:string}  $period
     * @return array<string, mixed>
     */
    public function present(
        PortfolioReportData $current,
        LeaseReportSnapshot $currentLeases,
        PortfolioReportData $previous,
        LeaseReportSnapshot $previousLeases,
        array $period,
    ): array {
        $currentPositions = $this->positions($current, $currentLeases);
        $previousPositions = $this->positions($previous, $previousLeases);
        $currencies = collect([...array_keys($currentPositions), ...array_keys($previousPositions)])
            ->unique()
            ->sort()
            ->values();

        return [
            'period' => $period,
            'currencyPositions' => $currencies
                ->map(fn (string $currency): array => [
                    'currency' => $currency,
                    'metrics' => $this->currencyMetrics(
                        $currentPositions[$currency] ?? [],
                        $previousPositions[$currency] ?? [],
                    ),
                ])
                ->all(),
            'serviceMetrics' => [
                $this->metric(
                    'maintenance_opened',
                    $current->maintenanceRequests->count(),
                    $previous->maintenanceRequests->count(),
                    'number',
                ),
                $this->metric(
                    'maintenance_resolved',
                    $current->resolvedMaintenanceRequests->count(),
                    $previous->resolvedMaintenanceRequests->count(),
                    'number',
                ),
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function positions(
        PortfolioReportData $data,
        LeaseReportSnapshot $leases,
    ): array {
        return collect($this->currencies->present($data, $leases))
            ->keyBy('currency')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $previous
     * @return list<array<string, mixed>>
     */
    private function currencyMetrics(array $current, array $previous): array
    {
        return [
            $this->metric('collected', $current['revenue'] ?? 0, $previous['revenue'] ?? 0, 'money'),
            $this->metric('expenses', $current['expenses'] ?? 0, $previous['expenses'] ?? 0, 'money'),
            $this->metric('net_position', $current['net'] ?? 0, $previous['net'] ?? 0, 'money'),
            $this->metric('scheduled_due', $current['scheduledDue'] ?? 0, $previous['scheduledDue'] ?? 0, 'money'),
            $this->metric(
                'collection_health',
                $current['collectionRate'] ?? 0,
                $previous['collectionRate'] ?? 0,
                'percent',
                true,
            ),
        ];
    }

    /** @return array{key:string,format:string,current:float,previous:float,change:float|null,changeKind:string,trend:string} */
    private function metric(
        string $key,
        float|int $current,
        float|int $previous,
        string $format,
        bool $percentagePoints = false,
    ): array {
        $current = (float) $current;
        $previous = (float) $previous;
        $change = $percentagePoints
            ? round($current - $previous, 1)
            : $this->percentageChange($current, $previous);

        return [
            'key' => $key,
            'format' => $format,
            'current' => $current,
            'previous' => $previous,
            'change' => $change,
            'changeKind' => $percentagePoints ? 'points' : 'percent',
            'trend' => $change === null
                ? 'new'
                : ($change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat')),
        ];
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.005) {
            return abs($current) < 0.005 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
