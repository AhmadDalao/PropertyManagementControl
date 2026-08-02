<?php

namespace App\Modules\Reports\Support;

final class ArrearsAgingOptions
{
    /** @var list<string> */
    public const array BUCKETS = [
        'days_1_30',
        'days_31_60',
        'days_61_90',
        'over_90',
    ];

    public static function bucketFor(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue <= 30 => 'days_1_30',
            $daysOverdue <= 60 => 'days_31_60',
            $daysOverdue <= 90 => 'days_61_90',
            default => 'over_90',
        };
    }
}
