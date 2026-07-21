<?php

namespace App\Modules\Payments\Actions;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Payments\Support\PaymentAccess;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManagePayments
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly PaymentAllocator $allocator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Payment
    {
        $this->access->ensureManager($actor);
        $lease = Lease::query()->whereKey((int) $data['lease_id'])->firstOrFail();
        $this->portfolios->ensureAccess($actor, $lease->portfolio_id);
        $this->ensureLeaseCanReceivePayment($lease);

        return DB::transaction(function () use ($actor, $lease, $data): Payment {
            $lockedLease = Lease::query()->lockForUpdate()->whereKey($lease->id)->firstOrFail();
            $this->ensureLeaseCanReceivePayment($lockedLease);

            $payment = Payment::query()->create([
                'portfolio_id' => $lockedLease->portfolio_id,
                'lease_id' => $lockedLease->id,
                'tenant_profile_id' => $lockedLease->tenant_profile_id,
                'recorded_by_user_id' => $actor->id,
                'reference' => $data['reference'] ?? $this->nextReference(),
                'type' => $data['type'],
                'method' => $data['method'],
                'status' => $data['status'],
                'received_on' => $data['received_on'],
                'amount' => $data['amount'],
                'currency' => Str::upper((string) $lockedLease->currency),
                'notes' => $data['notes'] ?? null,
            ]);

            if ($payment->status === 'posted') {
                $this->allocator->allocate($payment);
            }

            return $payment->fresh(['allocations']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Payment $payment, array $data): Payment
    {
        $this->access->ensureCanManage($actor, $payment);

        return DB::transaction(function () use ($payment, $data): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->whereKey($payment->id)->firstOrFail();
            $originalStatus = (string) $lockedPayment->status;
            $nextStatus = (string) $data['status'];

            if ($originalStatus === 'void' && $nextStatus !== 'void') {
                throw ValidationException::withMessages([
                    'status' => trans('app.errors.payment_voided_locked'),
                ]);
            }

            if ($nextStatus === 'void' && $originalStatus !== 'void') {
                $this->allocator->voidPayment($lockedPayment);
                $lockedPayment->refresh()->update(['notes' => $data['notes'] ?? null]);

                return $lockedPayment->fresh(['allocations']);
            }

            if ($originalStatus === 'posted' && $nextStatus === 'pending') {
                $this->allocator->reverse($lockedPayment);
            }

            $lockedPayment->update([
                'status' => $nextStatus,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($originalStatus !== 'posted' && $nextStatus === 'posted') {
                $this->allocator->allocate($lockedPayment);
            }

            return $lockedPayment->fresh(['allocations']);
        });
    }

    public function void(User $actor, Payment $payment): Payment
    {
        return $this->update($actor, $payment, [
            'status' => 'void',
            'notes' => $payment->notes,
        ]);
    }

    private function ensureLeaseCanReceivePayment(Lease $lease): void
    {
        $tenantExists = $lease->tenant_profile_id
            && TenantProfile::query()
                ->whereKey($lease->tenant_profile_id)
                ->where('portfolio_id', $lease->portfolio_id)
                ->exists();

        if (! in_array($lease->status, PaymentOptions::PAYABLE_LEASE_STATUSES, true) || ! $tenantExists) {
            throw ValidationException::withMessages([
                'lease_id' => trans('app.errors.payment_lease_not_payable'),
            ]);
        }
    }

    private function nextReference(): string
    {
        do {
            $reference = 'PAY-'.Str::upper(Str::random(10));
        } while (Payment::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
