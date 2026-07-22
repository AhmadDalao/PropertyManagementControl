<?php

namespace App\Modules\Payments\Data;

use App\Models\Asset;
use App\Models\Payment;
use App\Models\User;

final readonly class PaymentDetailData
{
    public function __construct(
        public Payment $payment,
        public User $actor,
        public bool $adminMode,
        public ?Asset $asset,
    ) {}
}
