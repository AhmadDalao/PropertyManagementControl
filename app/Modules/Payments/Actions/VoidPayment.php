<?php

namespace App\Modules\Payments\Actions;

use App\Models\Payment;
use App\Models\User;

final class VoidPayment
{
    public function __construct(private readonly UpdatePayment $update) {}

    public function handle(User $actor, Payment $payment): Payment
    {
        return $this->update->handle($actor, $payment, [
            'status' => 'void',
            'notes' => $payment->notes,
        ]);
    }
}
