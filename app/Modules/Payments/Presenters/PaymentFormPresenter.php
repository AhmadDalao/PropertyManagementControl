<?php

namespace App\Modules\Payments\Presenters;

use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Queries\PaymentFormOptionsQuery;
use App\Modules\Payments\Support\PaymentAccess;

final class PaymentFormPresenter
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly PaymentFormOptionsQuery $options,
        private readonly PaymentCreateFormPresenter $create,
        private readonly PaymentEditFormPresenter $edit,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?Payment $payment = null, array $defaults = []): array
    {
        if ($payment) {
            $this->access->ensureCanManage($actor, $payment);

            return $this->edit->present($payment);
        }

        $this->access->ensureManager($actor);

        return $this->create->present($this->options->get($actor, $defaults));
    }
}
