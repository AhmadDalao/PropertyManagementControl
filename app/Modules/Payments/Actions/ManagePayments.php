<?php

namespace App\Modules\Payments\Actions;

use App\Models\Payment;
use App\Models\User;

final class ManagePayments
{
    public function __construct(
        private readonly CreatePayment $create,
        private readonly UpdatePayment $update,
        private readonly VoidPayment $void,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Payment
    {
        return $this->create->handle($actor, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Payment $payment, array $data): Payment
    {
        return $this->update->handle($actor, $payment, $data);
    }

    public function void(User $actor, Payment $payment): Payment
    {
        return $this->void->handle($actor, $payment);
    }
}
