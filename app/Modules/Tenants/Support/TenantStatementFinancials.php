<?php

namespace App\Modules\Tenants\Support;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class TenantStatementFinancials
{
    /**
     * @param  Collection<int, Lease>  $leases
     * @return Collection<int, LeaseInstallment>
     */
    public function periodInstallments(
        Collection $leases,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): Collection {
        return $leases
            ->whereIn('status', ['active', 'expired', 'terminated'])
            ->flatMap(fn (Lease $lease) => $lease->installments)
            ->filter(fn (LeaseInstallment $installment): bool => $installment->due_date?->betweenIncluded($start, $end) ?? false);
    }

    /**
     * @param  Collection<int, Lease>  $leases
     * @param  array<string, float>  $paymentTotals
     * @param  Collection<int, LeaseInstallment>  $installments
     * @return array<int, array<string, float|string>>
     */
    public function summaries(
        Collection $leases,
        array $paymentTotals,
        Collection $installments,
        CarbonImmutable $arrearsCutoff,
    ): array {
        $currencies = $leases
            ->pluck('currency')
            ->merge(array_keys($paymentTotals))
            ->filter()
            ->map(fn (mixed $currency): string => (string) $currency)
            ->unique()
            ->sort()
            ->values();

        if ($currencies->isEmpty()) {
            $currencies = collect(['SAR']);
        }

        return $currencies->map(function (string $currency) use ($leases, $paymentTotals, $installments, $arrearsCutoff): array {
            $currencyLeases = $leases
                ->where('currency', $currency)
                ->whereIn('status', ['active', 'expired', 'terminated']);
            $currencyInstallments = $installments->filter(
                fn (LeaseInstallment $installment): bool => ($installment->lease?->currency ?: 'SAR') === $currency,
            );

            return [
                'currency' => $currency,
                'scheduled_due' => (float) $currencyInstallments->sum('amount_due'),
                'scheduled_paid' => (float) $currencyInstallments->sum(
                    fn (LeaseInstallment $installment): float => min(
                        (float) $installment->amount_due,
                        (float) $installment->amount_paid,
                    ),
                ),
                'received' => (float) ($paymentTotals[$currency] ?? 0),
                'contract_balance' => (float) $currencyLeases->sum(
                    fn (Lease $lease): float => $lease->balance_remaining,
                ),
                'overdue' => (float) $currencyLeases->sum(
                    fn (Lease $lease): float => $this->overdue($lease, $arrearsCutoff),
                ),
            ];
        })->all();
    }

    public function overdue(Lease $lease, CarbonImmutable $cutoff): float
    {
        return (float) $lease->installments
            ->filter(fn (LeaseInstallment $installment): bool => $installment->due_date?->lessThan($cutoff) ?? false)
            ->sum(fn (LeaseInstallment $installment): float => $installment->remaining_amount);
    }
}
