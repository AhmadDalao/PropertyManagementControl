<?php

namespace App\Modules\Maintenance\Support;

final class MaintenanceOptions
{
    /** @var array<int, string> */
    public const CATEGORIES = ['electricity', 'plumbing', 'ac', 'general'];

    /** @var array<int, string> */
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    /** @var array<int, string> */
    public const STATUSES = ['open', 'in_progress', 'resolved', 'cancelled'];

    private function __construct() {}
}
