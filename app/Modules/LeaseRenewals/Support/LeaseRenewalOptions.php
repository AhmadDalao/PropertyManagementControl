<?php

namespace App\Modules\LeaseRenewals\Support;

final class LeaseRenewalOptions
{
    /** @var array<int, string> */
    public const QUEUES = ['attention', 'upcoming', 'prepared', 'expired'];

    /** @var array<int, string> */
    public const HORIZONS = ['30', '60', '90', '120', 'all'];

    /** @var array<int, string> */
    public const LEASE_STATUSES = ['active', 'expired'];
}
