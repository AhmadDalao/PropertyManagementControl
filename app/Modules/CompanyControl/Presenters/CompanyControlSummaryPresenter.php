<?php

namespace App\Modules\CompanyControl\Presenters;

use App\Modules\PortfolioControl\Support\PortfolioControlCurrencyPositions;
use Illuminate\Support\Collection;

final readonly class CompanyControlSummaryPresenter
{
    public function __construct(
        private PortfolioControlCurrencyPositions $positions,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function present(Collection $rows): array
    {
        $financial = [];
        $valuations = [];

        foreach ($rows as $row) {
            foreach ($row['currency_totals'] as $position) {
                $currency = $position['currency'];
                $financial[$currency] ??= $this->positions->empty($currency);

                foreach (['scheduled_due', 'scheduled_paid', 'arrears', 'collected', 'expenses'] as $field) {
                    $financial[$currency][$field] += (float) $position[$field];
                }
            }
            foreach ($row['valuation_totals'] as $position) {
                $currency = $position['currency'];
                $valuations[$currency] = ($valuations[$currency] ?? 0.0)
                    + (float) $position['amount'];
            }
        }

        foreach ($financial as &$position) {
            $position['collection_rate'] = $position['scheduled_due'] > 0
                ? round(min(100, ($position['scheduled_paid'] / $position['scheduled_due']) * 100), 1)
                : 0.0;
            $position['net'] = $position['collected'] - $position['expenses'];
        }
        unset($position);
        ksort($financial);
        ksort($valuations);
        $rentable = (int) $rows->sum('rentable_units');
        $occupied = (int) $rows->sum('occupied_units');

        return [
            'portfolios' => $rows->count(),
            'needs_action' => $rows->where('attention', 'risk')->count(),
            'properties' => (int) $rows->sum('properties'),
            'active_accounts' => (int) $rows->sum('accounts.active'),
            'occupancy_rate' => $rentable > 0
                ? round(($occupied / $rentable) * 100, 1)
                : 0.0,
            'open_requests' => (int) $rows->sum('open_requests'),
            'valuation_totals' => collect($valuations)
                ->map(fn (float $amount, string $currency): array => compact('currency', 'amount'))
                ->values()
                ->all(),
            'currency_totals' => array_values($financial),
        ];
    }
}
