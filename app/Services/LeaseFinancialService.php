<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

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
                'status' => $this->installmentStatus($start, $lease->deposit_amount, 0),
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
                'status' => $this->installmentStatus(
                    $this->dueDateForPeriod($lease, $cursor),
                    $rentAmount,
                    0,
                ),
            ]);

            $cursor = $cursor->addMonthsNoOverflow($stepMonths);
        }
    }

    public function allocatePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status !== 'posted' || ! $lockedPayment->lease_id) {
                return;
            }

            $this->reverseAllocationsLocked($lockedPayment);

            $remainingCents = $this->cents($lockedPayment->amount);
            $installments = LeaseInstallment::query()
                ->where('lease_id', $lockedPayment->lease_id)
                ->orderBy('due_date')
                ->orderBy('sequence')
                ->lockForUpdate()
                ->get();

            /** @var LeaseInstallment $installment */
            foreach ($installments as $installment) {
                if ($remainingCents <= 0) {
                    break;
                }

                $dueCents = $this->cents($installment->amount_due);
                $paidCents = $this->cents($installment->amount_paid);
                $openCents = max(0, $dueCents - $paidCents);

                if ($openCents === 0) {
                    continue;
                }

                $allocatedCents = min($remainingCents, $openCents);
                $paidCents += $allocatedCents;

                PaymentAllocation::query()->create([
                    'payment_id' => $lockedPayment->id,
                    'lease_installment_id' => $installment->id,
                    'allocation_type' => $installment->line_type,
                    'amount' => $this->decimal($allocatedCents),
                ]);

                $installment->amount_paid = $this->decimal($paidCents);
                $installment->status = $this->installmentStatus(
                    CarbonImmutable::parse($installment->due_date),
                    $dueCents,
                    $paidCents,
                    true,
                );
                $installment->paid_at = $installment->status === 'paid' ? now() : null;
                $installment->save();

                $remainingCents -= $allocatedCents;
            }
        });
    }

    public function reverseAllocations(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $this->reverseAllocationsLocked($lockedPayment);
        });
    }

    public function voidPayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $this->reverseAllocationsLocked($lockedPayment);
            $lockedPayment->update(['status' => 'void']);
        });
    }

    public function refreshInstallmentStatuses(): int
    {
        $updated = 0;

        LeaseInstallment::query()
            ->where('status', '!=', 'paid')
            ->orderBy('id')
            ->chunkById(250, function ($installments) use (&$updated): void {
                /** @var LeaseInstallment $installment */
                foreach ($installments as $installment) {
                    $status = $this->installmentStatus(
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

    private function reverseAllocationsLocked(Payment $payment): void
    {
        $allocations = PaymentAllocation::query()
            ->where('payment_id', $payment->id)
            ->lockForUpdate()
            ->get();

        foreach ($allocations as $allocation) {
            $installment = LeaseInstallment::query()
                ->lockForUpdate()
                ->find($allocation->lease_installment_id);

            if (! $installment) {
                continue;
            }

            $dueCents = $this->cents($installment->amount_due);
            $paidCents = max(
                0,
                $this->cents($installment->amount_paid) - $this->cents($allocation->amount),
            );
            $installment->amount_paid = $this->decimal($paidCents);
            $installment->status = $this->installmentStatus(
                CarbonImmutable::parse($installment->due_date),
                $dueCents,
                $paidCents,
                true,
            );
            $installment->paid_at = $installment->status === 'paid' ? $installment->paid_at : null;
            $installment->save();
        }

        PaymentAllocation::query()->where('payment_id', $payment->id)->delete();
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

    private function installmentStatus(
        CarbonImmutable $dueDate,
        mixed $amountDue,
        mixed $amountPaid,
        bool $amountsAreCents = false,
    ): string {
        $dueCents = $amountsAreCents ? (int) $amountDue : $this->cents($amountDue);
        $paidCents = $amountsAreCents ? (int) $amountPaid : $this->cents($amountPaid);

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

    private function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
