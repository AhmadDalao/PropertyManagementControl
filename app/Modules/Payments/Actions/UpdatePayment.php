<?php

namespace App\Modules\Payments\Actions;

use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Support\PaymentAccess;
use App\Modules\Payments\Support\PaymentAttributes;
use App\Modules\Payments\Support\PaymentInputGuard;
use App\Modules\Payments\Support\PaymentTransitionGuard;
use Illuminate\Support\Facades\DB;

final class UpdatePayment
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly PaymentInputGuard $input,
        private readonly PaymentAttributes $attributes,
        private readonly PaymentTransitionGuard $transitions,
        private readonly PaymentAllocator $allocator,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, Payment $payment, array $data): Payment
    {
        $this->access->ensureCanManage($actor, $payment);
        $this->input->validateUpdate($data);

        return DB::transaction(function () use ($actor, $payment, $data): Payment {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $this->access->ensureCanManage($actor, $locked);
            $current = (string) $locked->status;
            $target = (string) $data['status'];
            $this->transitions->ensureAllowed($current, $target);

            if ($target === 'void' && $current !== 'void') {
                $this->allocator->voidPayment($locked);
                $locked->refresh()->update(['notes' => $this->attributes->notes($data)]);

                return $locked->fresh(['allocations']);
            }

            if ($current === 'posted' && $target === 'pending') {
                $this->allocator->reverse($locked);
            }

            $locked->update($this->attributes->forUpdate($data));

            if ($current !== 'posted' && $target === 'posted') {
                $this->allocator->allocate($locked);
            }

            return $locked->fresh(['allocations']);
        }, attempts: 3);
    }
}
