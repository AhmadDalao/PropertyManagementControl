<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Carbon\CarbonImmutable;

class LeaseFinancialService
{
    public function syncInstallments(Lease $lease): void
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
                'status' => 'pending',
            ]);
        }

        while ($cursor->lessThanOrEqualTo($end)) {
            $periodEnd = $cursor->addMonthsNoOverflow($stepMonths)->subDay();

            if ($periodEnd->greaterThan($end)) {
                $periodEnd = $end;
            }

            $lease->installments()->create([
                'sequence' => $sequence++,
                'line_type' => 'rent',
                'label' => $this->rentLabel($cursor, $periodEnd),
                'period_start' => $cursor,
                'period_end' => $periodEnd,
                'due_date' => $this->dueDateForPeriod($lease, $cursor),
                'amount_due' => $rentAmount,
                'status' => 'pending',
            ]);

            $cursor = $cursor->addMonthsNoOverflow($stepMonths);
        }
    }

    public function allocatePayment(Payment $payment): void
    {
        if ($payment->status !== 'posted') {
            return;
        }

        $payment->loadMissing('lease.installments');

        if (! $payment->lease) {
            return;
        }

        $this->reverseAllocations($payment);

        $remaining = (float) $payment->amount;
        /** @var LeaseInstallment $installment */
        foreach ($payment->lease->installments()->orderBy('due_date')->get() as $installment) {
            if ($remaining <= 0) {
                break;
            }

            $openAmount = max(0, (float) $installment->amount_due - (float) $installment->amount_paid);
            if ($openAmount <= 0) {
                continue;
            }

            $allocated = min($remaining, $openAmount);

            PaymentAllocation::query()->create([
                'payment_id' => $payment->id,
                'lease_installment_id' => $installment->id,
                'allocation_type' => $installment->line_type,
                'amount' => $allocated,
            ]);

            $installment->amount_paid = (float) $installment->amount_paid + $allocated;
            $installment->status = $installment->amount_paid >= $installment->amount_due ? 'paid' : 'partial';
            $installment->paid_at = $installment->status === 'paid' ? now() : null;
            $installment->save();

            $remaining -= $allocated;
        }
    }

    public function reverseAllocations(Payment $payment): void
    {
        $payment->loadMissing('allocations.leaseInstallment');

        foreach ($payment->allocations as $allocation) {
            $installment = $allocation->leaseInstallment;

            if (! $installment) {
                continue;
            }

            $installment->amount_paid = max(0, (float) $installment->amount_paid - (float) $allocation->amount);
            $installment->status = match (true) {
                $installment->amount_paid <= 0 => 'pending',
                $installment->amount_paid < $installment->amount_due => 'partial',
                default => 'paid',
            };
            $installment->paid_at = $installment->status === 'paid' ? $installment->paid_at : null;
            $installment->save();
        }

        $payment->allocations()->delete();
    }

    public function voidPayment(Payment $payment): void
    {
        $this->reverseAllocations($payment);
        $payment->update(['status' => 'void']);
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
}
