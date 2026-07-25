<?php

namespace App\Modules\LeaseRenewals\Support;

final class LeaseRenewalOptions
{
    /** @var array<string, string> */
    public const DEFAULT_FILTERS = [
        'queue' => 'attention',
        'horizon' => '90',
        'lease_status' => 'all',
        'property_id' => 'all',
        'sort' => 'ends_at',
        'direction' => 'asc',
    ];

    /** @var array<int, string> */
    public const QUEUES = ['attention', 'upcoming', 'prepared', 'expired'];

    /** @var array<int, string> */
    public const HORIZONS = ['30', '60', '90', '120', 'all'];

    /** @var array<int, string> */
    public const LEASE_STATUSES = ['active', 'expired'];
}
