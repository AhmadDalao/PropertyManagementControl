<?php

namespace App\Modules\Maintenance\Support;

final class MaintenanceVendorOptions
{
    /** @var array<int, string> */
    public const STATUSES = ['active', 'inactive'];

    private function __construct() {}
}
