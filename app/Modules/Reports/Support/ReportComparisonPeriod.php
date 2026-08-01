<?php

namespace App\Modules\Reports\Support;

use Carbon\CarbonImmutable;

final class ReportComparisonPeriod
{
    /**
     * @param  array{period?:string,date_from:string,date_to:string}  $filters
     * @return array{date_from:string,date_to:string}
     */
    public function previous(array $filters): array
    {
        $period = trim((string) ($filters['period'] ?? ReportPeriod::CUSTOM));
        $start = CarbonImmutable::parse($filters['date_from'])->startOfDay();
        $end = CarbonImmutable::parse($filters['date_to'])->startOfDay();

        [$previousStart, $previousEnd] = match ($period) {
            ReportPeriod::THIS_MONTH => $this->previousMonthToDate($start, $end),
            ReportPeriod::LAST_MONTH => [
                $start->subMonthNoOverflow()->startOfMonth(),
                $start->subMonthNoOverflow()->endOfMonth(),
            ],
            ReportPeriod::YEAR_TO_DATE => [
                $start->subYearNoOverflow(),
                $end->subYearNoOverflow(),
            ],
            default => $this->adjacentPeriod($start, $end),
        };

        return [
            'date_from' => $previousStart->toDateString(),
            'date_to' => $previousEnd->toDateString(),
        ];
    }

    /** @return array{CarbonImmutable,CarbonImmutable} */
    private function previousMonthToDate(
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $previousStart = $start->subMonthNoOverflow()->startOfMonth();
        $duration = max(1, (int) $start->diffInDays($end) + 1);
        $previousEnd = $previousStart->addDays($duration - 1);

        return [
            $previousStart,
            $previousEnd->min($previousStart->endOfMonth()),
        ];
    }

    /** @return array{CarbonImmutable,CarbonImmutable} */
    private function adjacentPeriod(
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $duration = max(1, (int) $start->diffInDays($end) + 1);
        $previousEnd = $start->subDay();

        return [$previousEnd->subDays($duration - 1), $previousEnd];
    }
}
