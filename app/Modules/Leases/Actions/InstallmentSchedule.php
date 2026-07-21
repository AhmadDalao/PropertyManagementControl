<?php

namespace App\Modules\Leases\Actions;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use Carbon\CarbonImmutable;

class InstallmentSchedule
{
    public function sync(Lease $lease): void
    {
        $lease->installments()->delete();

        $start = CarbonImmutable::parse($lease->started_at)->startOfDay();
        $end = CarbonImmutable::parse($lease->ends_at)->startOfDay();
        $stepMonths = $this->frequencyMonths((string) $lease->payment_frequency);
        $cursor = $start;
        $sequence = 1;
        $rentAmount = $this->installmentRentAmount($lease);

        if ($lease->deposit_amount > 0) {
            $lease->installments()->create([
                'sequence' => $sequence++,
                'line_type' => 'deposit',
                'label' => 'Security deposit',
                'period_start' => $start,
                'period_end' => $start,
                'due_date' => $start,
                'amount_due' => $lease->deposit_amount,
                'status' => $this->status($start, $lease->deposit_amount, 0),
            ]);
        }

        while ($cursor->lessThanOrEqualTo($end)) {
            $periodEnd = $cursor->addMonthsNoOverflow($stepMonths)->subDay();

            if ($periodEnd->greaterThan($end)) {
                $periodEnd = $end;
            }

            $dueDate = $this->dueDateForPeriod($lease, $cursor);
            $lease->installments()->create([
                'sequence' => $sequence++,
                'line_type' => 'rent',
                'label' => $this->rentLabel($cursor, $periodEnd),
                'period_start' => $cursor,
                'period_end' => $periodEnd,
                'due_date' => $dueDate,
                'amount_due' => $rentAmount,
                'status' => $this->status($dueDate, $rentAmount, 0),
            ]);

            $cursor = $cursor->addMonthsNoOverflow($stepMonths);
        }
    }

    public function refreshStatuses(): int
    {
        $updated = 0;

        LeaseInstallment::query()
            ->where('status', '!=', 'paid')
            ->orderBy('id')
            ->chunkById(250, function ($installments) use (&$updated): void {
                foreach ($installments as $installment) {
                    $status = $this->status(
                        CarbonImmutable::parse($installment->due_date),
                        $installment->amount_due,
                        $installment->amount_paid,
                    );

                    if ($installment->status === $status) {
                        continue;
                    }

                    $installment->update(['status' => $status]);
                    $updated++;
                }
            });

        return $updated;
    }

    private function frequencyMonths(string $frequency): int
    {
        return match ($frequency) {
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };
    }

    private function installmentRentAmount(Lease $lease): float
    {
        return max(
            0,
            (float) $lease->rent_amount
            + (float) $lease->tax_amount
            - (float) $lease->discount_amount
        );
    }

    private function dueDateForPeriod(Lease $lease, CarbonImmutable $periodStart): CarbonImmutable
    {
        $billingDay = (int) ($lease->billing_day ?: $periodStart->day);
        $leaseStart = CarbonImmutable::parse($lease->started_at)->startOfDay();
        $dueDate = $periodStart->setDate(
            (int) $periodStart->year,
            (int) $periodStart->month,
            min($billingDay, $periodStart->daysInMonth),
        );

        return $dueDate->lessThan($leaseStart) ? $leaseStart : $dueDate;
    }

    private function rentLabel(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): string
    {
        if ($periodStart->isSameMonth($periodEnd)) {
            return sprintf('Rent %s', $periodStart->format('M Y'));
        }

        if ((int) $periodStart->day !== 1 || ! $periodEnd->isLastOfMonth()) {
            if ($periodStart->isSameYear($periodEnd)) {
                return sprintf('Rent %s-%s %s', $periodStart->format('M j'), $periodEnd->format('M j'), $periodStart->format('Y'));
            }

            return sprintf('Rent %s-%s', $periodStart->format('M j Y'), $periodEnd->format('M j Y'));
        }

        if ($periodStart->isSameYear($periodEnd)) {
            return sprintf('Rent %s-%s %s', $periodStart->format('M'), $periodEnd->format('M'), $periodStart->format('Y'));
        }

        return sprintf('Rent %s-%s', $periodStart->format('M Y'), $periodEnd->format('M Y'));
    }

    private function status(CarbonImmutable $dueDate, mixed $amountDue, mixed $amountPaid): string
    {
        $dueCents = $this->cents($amountDue);
        $paidCents = $this->cents($amountPaid);

        return match (true) {
            $paidCents >= $dueCents => 'paid',
            $dueDate->lessThan(today()) => 'overdue',
            $paidCents > 0 => 'partial',
            default => 'pending',
        };
    }

    private function cents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
