<?php

namespace App\Modules\PortfolioControl\Presenters;

use App\Models\User;
use App\Modules\PortfolioControl\Support\PortfolioControlCurrencyPositions;
use Illuminate\Support\Collection;

final readonly class PortfolioControlSummaryPresenter
{
    public function __construct(
        private PortfolioControlCurrencyPositions $positions,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function present(Collection $rows, User $actor): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            foreach ($this->positions->from($row) as $position) {
                $currency = $position['currency'];
                $grouped[$currency] ??= $this->positions->empty($currency);

                foreach ([
                    'scheduled_due',
                    'scheduled_paid',
                    'arrears',
                    'collected',
                    'expenses',
                ] as $field) {
                    $grouped[$currency][$field] += $position[$field];
                }
            }
        }

        if ($grouped === []) {
            $currency = strtoupper(
                trim((string) ($actor->portfolio?->default_currency ?: 'SAR')),
            ) ?: 'SAR';
            $grouped[$currency] = $this->positions->empty($currency);
        }

        ksort($grouped);
        $currencyTotals = array_values(array_map(
            static function (array $position): array {
                $position['collection_rate'] = $position['scheduled_due'] > 0
                    ? round(min(
                        100,
                        ($position['scheduled_paid'] / $position['scheduled_due']) * 100,
                    ), 1)
                    : 0.0;
                $position['net'] = $position['collected'] - $position['expenses'];

                return $position;
            },
            $grouped,
        ));
        $singleCurrency = count($currencyTotals) === 1
            ? $currencyTotals[0]
            : null;
        $rentable = (int) $rows->sum('rentable_units');
        $occupied = (int) $rows->sum('occupied_units');

        return [
            'properties' => $rows->count(),
            'risk' => $rows->where('attention', 'risk')->count(),
            'occupancy_rate' => $rentable > 0
                ? round(($occupied / $rentable) * 100, 1)
                : 0,
            'collection_rate' => $singleCurrency['collection_rate'] ?? null,
            'arrears' => $singleCurrency['arrears'] ?? null,
            'net' => $singleCurrency['net'] ?? null,
            'currency' => $singleCurrency['currency'] ?? null,
            'currency_count' => count($currencyTotals),
            'currency_totals' => $currencyTotals,
            'open_requests' => (int) $rows->sum('open_requests'),
            'expiring_leases' => (int) $rows->sum('expiring_leases'),
        ];
    }
}
