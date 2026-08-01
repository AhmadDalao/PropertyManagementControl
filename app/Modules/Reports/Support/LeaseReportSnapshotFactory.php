<?php

namespace App\Modules\Reports\Support;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Modules\Reports\Data\LeaseReportSnapshot;
use App\Modules\Reports\Data\PortfolioReportData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class LeaseReportSnapshotFactory
{
    /** @param array{date_from:string,date_to:string,portfolio_id:int|null,property_id?:int|null} $filters */
    public function make(PortfolioReportData $data, array $filters): LeaseReportSnapshot
    {
        $eligibleLeases = $data->leases->whereIn('status', ['active', 'expired']);
        $periodStart = CarbonImmutable::parse($filters['date_from'])->startOfDay();
        $periodEnd = CarbonImmutable::parse($filters['date_to'])->endOfDay();
        $scheduledInstallments = $eligibleLeases
            ->flatMap(fn (Lease $lease) => $lease->installments)
            ->filter(fn (LeaseInstallment $installment): bool => $installment->due_date?->betweenIncluded($periodStart, $periodEnd) ?? false);
        $arrearsCutoff = $this->arrearsCutoff($filters['date_to']);
        $arrearsLeases = $eligibleLeases
            ->map(fn (Lease $lease): array => [
                'lease' => $lease,
                'arrears_amount' => $this->arrearsAmount($lease, $arrearsCutoff),
            ])
            ->filter(fn (array $item): bool => $item['arrears_amount'] > 0)
            ->sortByDesc('arrears_amount')
            ->values();

        return new LeaseReportSnapshot(
            scheduledDue: (float) $scheduledInstallments->sum('amount_due'),
            scheduledPaid: (float) $scheduledInstallments->sum(
                fn (LeaseInstallment $installment): float => min(
                    (float) $installment->amount_due,
                    (float) $installment->amount_paid,
                ),
            ),
            arrears: (float) $arrearsLeases->sum('arrears_amount'),
            contractBalance: (float) $eligibleLeases->sum(
                fn (Lease $lease): float => $lease->balance_remaining,
            ),
            activeLeases: $data->leases->where('status', 'active')->count(),
            arrearsLeases: $arrearsLeases,
            currencyTotals: $this->currencyTotals(
                $eligibleLeases,
                $scheduledInstallments,
                $arrearsLeases,
            ),
        );
    }

    /**
     * @param  Collection<int, Lease>  $eligibleLeases
     * @param  Collection<int, LeaseInstallment>  $scheduledInstallments
     * @param  Collection<int, array{lease:Lease,arrears_amount:float}>  $arrearsLeases
     * @return array<string, array{
     *     currency:string,
     *     scheduledDue:float,
     *     scheduledPaid:float,
     *     arrears:float,
     *     contractBalance:float
     * }>
     */
    private function currencyTotals(
        Collection $eligibleLeases,
        Collection $scheduledInstallments,
        Collection $arrearsLeases,
    ): array {
        $totals = [];
        $leaseCurrencies = $eligibleLeases->mapWithKeys(
            fn (Lease $lease): array => [
                $lease->id => $this->currency($lease->currency),
            ],
        );

        foreach ($eligibleLeases as $lease) {
            $currency = $this->currency($lease->currency);
            $totals[$currency] ??= $this->emptyCurrencyTotal($currency);
            $totals[$currency]['contractBalance'] += (float) $lease->balance_remaining;
        }

        foreach ($scheduledInstallments as $installment) {
            $currency = $leaseCurrencies->get((int) $installment->lease_id, 'SAR');
            $totals[$currency] ??= $this->emptyCurrencyTotal($currency);
            $totals[$currency]['scheduledDue'] += (float) $installment->amount_due;
            $totals[$currency]['scheduledPaid'] += min(
                (float) $installment->amount_due,
                (float) $installment->amount_paid,
            );
        }

        foreach ($arrearsLeases as $item) {
            $currency = $this->currency($item['lease']->currency);
            $totals[$currency] ??= $this->emptyCurrencyTotal($currency);
            $totals[$currency]['arrears'] += (float) $item['arrears_amount'];
        }

        ksort($totals);

        return $totals;
    }

    /**
     * @return array{
     *     currency:string,
     *     scheduledDue:float,
     *     scheduledPaid:float,
     *     arrears:float,
     *     contractBalance:float
     * }
     */
    private function emptyCurrencyTotal(string $currency): array
    {
        return [
            'currency' => $currency,
            'scheduledDue' => 0.0,
            'scheduledPaid' => 0.0,
            'arrears' => 0.0,
            'contractBalance' => 0.0,
        ];
    }

    private function currency(?string $currency): string
    {
        $normalized = strtoupper(trim((string) $currency));

        return $normalized !== '' ? $normalized : 'SAR';
    }

    private function arrearsAmount(Lease $lease, CarbonImmutable $cutoff): float
    {
        return (float) $lease->installments
            ->filter(fn (LeaseInstallment $installment): bool => $installment->due_date?->lessThan($cutoff) ?? false)
            ->sum(fn (LeaseInstallment $installment): float => $installment->remaining_amount);
    }

    private function arrearsCutoff(string $dateTo): CarbonImmutable
    {
        $today = CarbonImmutable::today();
        $reportEnd = CarbonImmutable::parse($dateTo)->startOfDay();

        return $reportEnd->lessThan($today) ? $reportEnd->addDay() : $today;
    }
}
