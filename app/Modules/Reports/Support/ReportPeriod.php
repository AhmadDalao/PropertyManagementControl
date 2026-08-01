<?php

namespace App\Modules\Reports\Support;

use Carbon\CarbonImmutable;

final class ReportPeriod
{
    public const CUSTOM = 'custom';

    public const THIS_MONTH = 'this_month';

    public const LAST_MONTH = 'last_month';

    public const LAST_30_DAYS = 'last_30_days';

    public const YEAR_TO_DATE = 'year_to_date';

    /** @return array<int, string> */
    public function values(): array
    {
        return [
            self::CUSTOM,
            self::THIS_MONTH,
            self::LAST_MONTH,
            self::LAST_30_DAYS,
            self::YEAR_TO_DATE,
        ];
    }

    public function normalize(mixed $period): string
    {
        $value = trim((string) $period);

        return in_array($value, $this->values(), true) ? $value : self::CUSTOM;
    }

    public function rolling(mixed $period): bool
    {
        return $this->normalize($period) !== self::CUSTOM;
    }

    /**
     * @return array{date_from:string,date_to:string}
     */
    public function resolve(
        mixed $period,
        mixed $dateFrom = null,
        mixed $dateTo = null,
        ?CarbonImmutable $today = null,
    ): array {
        $today ??= CarbonImmutable::today();

        return match ($this->normalize($period)) {
            self::THIS_MONTH => [
                'date_from' => $today->startOfMonth()->toDateString(),
                'date_to' => $today->toDateString(),
            ],
            self::LAST_MONTH => [
                'date_from' => $today->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'date_to' => $today->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            self::LAST_30_DAYS => [
                'date_from' => $today->subDays(29)->toDateString(),
                'date_to' => $today->toDateString(),
            ],
            self::YEAR_TO_DATE => [
                'date_from' => $today->startOfYear()->toDateString(),
                'date_to' => $today->toDateString(),
            ],
            default => [
                'date_from' => trim((string) $dateFrom) !== ''
                    ? trim((string) $dateFrom)
                    : $today->startOfYear()->toDateString(),
                'date_to' => trim((string) $dateTo) !== ''
                    ? trim((string) $dateTo)
                    : $today->toDateString(),
            ],
        };
    }
}
