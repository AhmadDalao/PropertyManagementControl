<?php

namespace App\Modules\Payments\Actions;

use App\Models\Payment;
use App\Models\User;
use App\Modules\Notifications\Actions\SendOperationalActivityNotification;
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
        private readonly SendOperationalActivityNotification $notifications,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, Payment $payment, array $data): Payment
    {
        $this->access->ensureCanManage($actor, $payment);
        $this->input->validateUpdate($data);

        $previousStatus = (string) $payment->status;
        $updated = DB::transaction(function () use ($actor, $payment, $data, &$previousStatus): Payment {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $this->access->ensureCanManage($actor, $locked);
            $current = (string) $locked->status;
            $previousStatus = $current;
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

        $event = match (true) {
            $updated->status === 'posted' && $previousStatus !== 'posted' => 'payment_posted',
            $updated->status === 'void' && $previousStatus !== 'void' => 'payment_voided',
            $updated->status === 'pending' && $previousStatus === 'posted' => 'payment_reversed',
            default => null,
        };

        if ($event) {
            $this->notifications->payment($actor, $updated, $event);
        }

        return $updated;
    }
}
