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
        $cursor = $start;
        $sequence = 1;

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
            $periodEnd = $cursor->addMonthNoOverflow()->subDay();

            if ($periodEnd->greaterThan($end)) {
                $periodEnd = $end;
            }

            $lease->installments()->create([
                'sequence' => $sequence++,
                'line_type' => 'rent',
                'label' => sprintf('Rent %s', $cursor->format('M Y')),
                'period_start' => $cursor,
                'period_end' => $periodEnd,
                'due_date' => $cursor,
                'amount_due' => $lease->rent_amount,
                'status' => 'pending',
            ]);

            $cursor = $cursor->addMonthNoOverflow();
        }
    }

    public function allocatePayment(Payment $payment): void
    {
        $payment->loadMissing('lease.installments', 'allocations');

        if (! $payment->lease) {
            return;
        }

        $payment->allocations()->delete();

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
}
