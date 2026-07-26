<?php

namespace App\Modules\Maintenance\Support;

final class MaintenanceWorkOrderOptions
{
    /** @var array<int, string> */
    public const STATUSES = ['draft', 'scheduled', 'in_progress', 'completed', 'cancelled'];

    /** @var array<string, array<int, string>> */
    public const TRANSITIONS = [
        'draft' => ['draft', 'scheduled', 'cancelled'],
        'scheduled' => ['scheduled', 'in_progress', 'cancelled'],
        'in_progress' => ['in_progress', 'completed', 'cancelled'],
        'completed' => ['completed', 'in_progress'],
        'cancelled' => ['cancelled', 'draft'],
    ];

    private function __construct() {}
}
