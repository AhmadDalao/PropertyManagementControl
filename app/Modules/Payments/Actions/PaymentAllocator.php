<?php

namespace App\Modules\Payments\Actions;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentAllocator
{
    public function allocate(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = Payment::query()->lockForUpdate()->whereKey($payment->id)->firstOrFail();

            if ($lockedPayment->status !== 'posted' || ! $lockedPayment->lease_id) {
                return;
            }

            $lease = Lease::query()->lockForUpdate()->whereKey($lockedPayment->lease_id)->firstOrFail();
            $this->ensurePaymentMatchesLease($lockedPayment, $lease);
            $this->reverseLocked($lockedPayment);

            $remainingCents = $this->cents($lockedPayment->amount);
            $installments = LeaseInstallment::query()
                ->where('lease_id', $lockedPayment->lease_id)
                ->orderBy('due_date')
                ->orderBy('sequence')
                ->lockForUpdate()
                ->get();

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

                $installment->amount_paid = $paidCents / 100;
                $installment->status = $this->status(
                    CarbonImmutable::parse($installment->due_date),
                    $dueCents,
                    $paidCents,
                );
                $installment->paid_at = $installment->status === 'paid' ? now() : null;
                $installment->save();

                $remainingCents -= $allocatedCents;
            }
        });
    }

    public function reverse(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = Payment::query()->lockForUpdate()->whereKey($payment->id)->firstOrFail();
            $this->reverseLocked($lockedPayment);
        });
    }

    public function voidPayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $lockedPayment = Payment::query()->lockForUpdate()->whereKey($payment->id)->firstOrFail();
            $this->reverseLocked($lockedPayment);
            $lockedPayment->update(['status' => 'void']);
        });
    }

    private function reverseLocked(Payment $payment): void
    {
        $allocations = PaymentAllocation::query()
            ->where('payment_id', $payment->id)
            ->orderBy('lease_installment_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($allocations as $allocation) {
            $installment = LeaseInstallment::query()
                ->lockForUpdate()
                ->whereKey($allocation->lease_installment_id)
                ->first();

            if (! $installment) {
                continue;
            }

            $dueCents = $this->cents($installment->amount_due);
            $paidCents = max(
                0,
                $this->cents($installment->amount_paid) - $this->cents($allocation->amount),
            );
            $installment->amount_paid = $paidCents / 100;
            $installment->status = $this->status(
                CarbonImmutable::parse($installment->due_date),
                $dueCents,
                $paidCents,
            );
            $installment->paid_at = $installment->status === 'paid' ? $installment->paid_at : null;
            $installment->save();
        }

        PaymentAllocation::query()->where('payment_id', $payment->id)->delete();
    }

    private function ensurePaymentMatchesLease(Payment $payment, Lease $lease): void
    {
        if ($payment->portfolio_id !== $lease->portfolio_id) {
            throw ValidationException::withMessages([
                'lease_id' => trans('app.errors.lease_portfolio_mismatch'),
            ]);
        }

        if ($payment->tenant_profile_id !== $lease->tenant_profile_id) {
            throw ValidationException::withMessages([
                'lease_id' => trans('app.errors.payment_tenant_mismatch'),
            ]);
        }

        if (strtoupper((string) $payment->currency) !== strtoupper((string) $lease->currency)) {
            throw ValidationException::withMessages([
                'lease_id' => trans('app.errors.payment_currency_mismatch'),
            ]);
        }
    }

    private function status(CarbonImmutable $dueDate, int $dueCents, int $paidCents): string
    {
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
