<?php

namespace App\Modules\Dashboard\Support;

use Carbon\CarbonImmutable;

final class DashboardPeriod
{
    public const array VALUES = ['month', 'quarter', 'year'];

    public static function normalize(?string $period): string
    {
        return in_array($period, self::VALUES, true) ? $period : 'month';
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable} */
    public static function bounds(string $period): array
    {
        $now = CarbonImmutable::now();

        return match (self::normalize($period)) {
            'quarter' => [
                'start' => $now->startOfQuarter(),
                'end' => $now->endOfQuarter(),
            ],
            'year' => [
                'start' => $now->startOfYear(),
                'end' => $now->endOfYear(),
            ],
            default => [
                'start' => $now->startOfMonth(),
                'end' => $now->endOfMonth(),
            ],
        };
    }
}
