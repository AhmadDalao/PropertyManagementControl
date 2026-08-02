<?php

namespace App\Modules\ActionCenter\Support;

final class ActionCenterOptions
{
    /** @var list<string> */
    public const TYPES = [
        'collection',
        'maintenance',
        'renewal',
        'move_out',
        'document_expiry',
    ];

    /** @var list<string> */
    public const PRIORITIES = ['critical', 'high', 'normal'];

    /** @var list<int> */
    public const PAGE_SIZES = [12, 24, 48];

    private function __construct() {}
}
