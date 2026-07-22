<?php

namespace App\Modules\Payments\Actions;

use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Support\PaymentAccess;
use App\Modules\Payments\Support\PaymentAttributes;
use App\Modules\Payments\Support\PaymentInputGuard;
use App\Modules\Payments\Support\PaymentLeaseGuard;
use Illuminate\Support\Facades\DB;

final class CreatePayment
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly PaymentInputGuard $input,
        private readonly PaymentLeaseGuard $leases,
        private readonly PaymentAttributes $attributes,
        private readonly PaymentAllocator $allocator,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): Payment
    {
        $this->access->ensureManager($actor);
        $this->input->validateCreate($data);

        return DB::transaction(function () use ($actor, $data): Payment {
            $lease = $this->leases->payable($actor, (int) $data['lease_id']);
            $payment = Payment::query()->create($this->attributes->forCreate($actor, $lease, $data));

            if ($payment->status === 'posted') {
                $this->allocator->allocate($payment);
            }

            return $payment->fresh(['allocations']);
        }, attempts: 3);
    }
}
