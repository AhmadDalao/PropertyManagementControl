<?php

namespace App\Modules\Leases\Support;

final class LeaseOptions
{
    /** @var array<int, string> */
    public const STATUSES = ['draft', 'active', 'expired', 'terminated'];

    /** @var array<int, string> */
    public const PAYMENT_FREQUENCIES = ['monthly', 'quarterly', 'yearly'];

    /** @var array<int, string> */
    public const TENANT_DOCUMENT_TYPES = ['lease_contract', 'signed_contract', 'tenant_statement'];

    private function __construct() {}
}
