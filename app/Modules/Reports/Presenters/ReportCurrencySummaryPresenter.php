<?php

namespace App\Modules\Reports\Presenters;

use App\Modules\Reports\Data\LeaseReportSnapshot;
use App\Modules\Reports\Data\PortfolioReportData;

final class ReportCurrencySummaryPresenter
{
    /**
     * @return list<array{
     *     currency:string,
     *     revenue:float,
     *     expenses:float,
     *     net:float,
     *     scheduledDue:float,
     *     scheduledPaid:float,
     *     collectionRate:float,
     *     arrears:float,
     *     contractBalance:float
     * }>
     */
    public function present(
        PortfolioReportData $data,
        LeaseReportSnapshot $leases,
    ): array {
        $totals = $leases->currencyTotals;

        foreach ($totals as $currency => $total) {
            $totals[$currency] = $total + $this->emptyTotal($currency);
        }

        foreach ($data->payments as $payment) {
            $currency = $this->currency($payment->currency);
            $totals[$currency] ??= $this->emptyTotal($currency);
            $totals[$currency]['revenue'] += (float) $payment->amount;
        }

        foreach ($data->expenses as $expense) {
            $currency = $this->currency($expense->currency);
            $totals[$currency] ??= $this->emptyTotal($currency);
            $totals[$currency]['expenses'] += (float) $expense->amount;
        }

        if ($totals === []) {
            foreach ($data->assets as $asset) {
                $currency = $this->currency($asset->currency);
                $totals[$currency] ??= $this->emptyTotal($currency);
            }
        }

        if ($totals === []) {
            $totals['SAR'] = $this->emptyTotal('SAR');
        }

        ksort($totals);

        return array_values(array_map(function (array $total): array {
            $total += $this->emptyTotal($total['currency']);
            $total['net'] = $total['revenue'] - $total['expenses'];
            $total['collectionRate'] = $total['scheduledDue'] > 0
                ? round(min(
                    100,
                    ($total['scheduledPaid'] / $total['scheduledDue']) * 100,
                ), 2)
                : 0.0;

            return $total;
        }, $totals));
    }

    /**
     * @return array{
     *     currency:string,
     *     revenue:float,
     *     expenses:float,
     *     net:float,
     *     scheduledDue:float,
     *     scheduledPaid:float,
     *     collectionRate:float,
     *     arrears:float,
     *     contractBalance:float
     * }
     */
    private function emptyTotal(string $currency): array
    {
        return [
            'currency' => $currency,
            'revenue' => 0.0,
            'expenses' => 0.0,
            'net' => 0.0,
            'scheduledDue' => 0.0,
            'scheduledPaid' => 0.0,
            'collectionRate' => 0.0,
            'arrears' => 0.0,
            'contractBalance' => 0.0,
        ];
    }

    private function currency(?string $currency): string
    {
        $normalized = strtoupper(trim((string) $currency));

        return $normalized !== '' ? $normalized : 'SAR';
    }
}
