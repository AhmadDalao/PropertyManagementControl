<?php

namespace Tests\Unit;

use App\Modules\Reports\Support\ReportComparisonPeriod;
use App\Modules\Reports\Support\ReportPeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReportComparisonPeriodTest extends TestCase
{
    /**
     * @param  array{period:string,date_from:string,date_to:string}  $filters
     * @param  array{date_from:string,date_to:string}  $expected
     */
    #[Test]
    #[DataProvider('periods')]
    public function it_returns_the_previous_equivalent_period(
        array $filters,
        array $expected,
    ): void {
        $this->assertSame(
            $expected,
            (new ReportComparisonPeriod)->previous($filters),
        );
    }

    /** @return iterable<string, array{array<string,string>,array<string,string>}> */
    public static function periods(): iterable
    {
        yield 'month to date' => [
            [
                'period' => ReportPeriod::THIS_MONTH,
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-15',
            ],
            ['date_from' => '2026-07-01', 'date_to' => '2026-07-15'],
        ];

        yield 'full previous month' => [
            [
                'period' => ReportPeriod::LAST_MONTH,
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ],
            ['date_from' => '2026-06-01', 'date_to' => '2026-06-30'],
        ];

        yield 'year to date' => [
            [
                'period' => ReportPeriod::YEAR_TO_DATE,
                'date_from' => '2026-01-01',
                'date_to' => '2026-08-15',
            ],
            ['date_from' => '2025-01-01', 'date_to' => '2025-08-15'],
        ];

        yield 'adjacent custom range' => [
            [
                'period' => ReportPeriod::CUSTOM,
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-15',
            ],
            ['date_from' => '2026-07-17', 'date_to' => '2026-07-31'],
        ];
    }
}
