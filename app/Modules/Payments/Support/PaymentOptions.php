<?php

namespace App\Modules\Payments\Support;

final class PaymentOptions
{
    /** @var array<int, string> */
    public const STATUSES = ['posted', 'pending', 'void'];

    /** @var array<int, string> */
    public const CREATE_STATUSES = ['posted', 'pending'];

    /** @var array<int, string> */
    public const TYPES = ['rent', 'deposit', 'fee'];

    /** @var array<int, string> */
    public const METHODS = ['bank_transfer', 'cash', 'card'];

    /** @var array<int, string> */
    public const PAYABLE_LEASE_STATUSES = ['active', 'expired', 'terminated'];

    /** @var array<int, string> */
    public const TENANT_DOCUMENT_TYPES = ['receipt'];
}
