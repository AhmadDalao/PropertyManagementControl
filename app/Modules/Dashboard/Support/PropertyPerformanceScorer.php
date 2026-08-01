<?php

namespace App\Modules\Dashboard\Support;

final class PropertyPerformanceScorer
{
    public function __construct(
        private readonly PropertyPerformanceCurrencySummary $currencies,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function score(array $row): array
    {
        $defaultCurrency = $this->currencies->currency(
            is_string($row['currency'] ?? null) ? $row['currency'] : null,
        );
        $row['occupancy_rate'] = $row['rentable_units'] > 0
            ? round(($row['occupied_units'] / $row['rentable_units']) * 100, 1)
            : 0;
        $currencyTotals = $this->currencies->score(
            $row['currency_totals'] ?? null,
        );
        $currencyTotals = $currencyTotals !== []
            ? $currencyTotals
            : [$this->currencies->empty($defaultCurrency)];
        $singleCurrency = count($currencyTotals) === 1
            ? $currencyTotals[0]
            : null;
        $row['currency_count'] = count($currencyTotals);
        $row['currency_totals'] = $currencyTotals;
        $row['currency'] = $singleCurrency['currency'] ?? null;
        $row['scheduled_due'] = $singleCurrency['scheduled_due'] ?? null;
        $row['scheduled_paid'] = $singleCurrency['scheduled_paid'] ?? null;
        $row['collection_rate'] = $singleCurrency['collection_rate'] ?? null;
        $row['arrears'] = $singleCurrency['arrears'] ?? null;
        $row['collected'] = $singleCurrency['collected'] ?? null;
        $row['expenses'] = $singleCurrency['expenses'] ?? null;
        $row['net'] = $singleCurrency['net'] ?? null;
        $row['attention_score'] = ($this->currencies->hasArrears($currencyTotals) ? 4 : 0)
            + ($row['open_requests'] > 0 ? 2 : 0)
            + ($this->currencies->hasWeakCollection($currencyTotals) ? 2 : 0)
            + ($row['rentable_units'] > 0 && $row['occupancy_rate'] < 70 ? 2 : 0)
            + ($row['expiring_leases'] > 0 ? 1 : 0);
        $row['attention'] = $row['attention_score'] >= 4
            ? 'risk'
            : ($row['attention_score'] > 0 ? 'watch' : 'on_track');

        return $row;
    }
}
