<?php

namespace App\Modules\LeaseMoveOuts\Support;

final class LeaseMoveOutOptions
{
    /** @var list<string> */
    public const STATUSES = ['planned', 'completed', 'cancelled'];

    /** @var list<string> */
    public const QUEUES = ['attention', 'upcoming', 'ready', 'completed', 'cancelled'];

    /** @var list<string> */
    public const HORIZONS = ['30', '60', '90', '120', 'all'];

    /** @var list<string> */
    public const REASONS = [
        'natural_expiry',
        'tenant_notice',
        'owner_notice',
        'mutual_agreement',
        'breach',
        'other',
    ];

    /** @var list<string> */
    public const DEPOSIT_DISPOSITIONS = [
        'pending',
        'refund_full',
        'refund_partial',
        'retained',
        'not_applicable',
    ];

    /** @var list<string> */
    public const REQUIRED_DOCUMENT_TYPES = [
        'termination_notice',
        'move_out_inspection',
    ];

    private function __construct() {}
}
