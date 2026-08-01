<?php

namespace App\Modules\Reports\Data;

use App\Models\Lease;
use Illuminate\Support\Collection;

final readonly class LeaseReportSnapshot
{
    /**
     * @param  Collection<int, array{lease:Lease,arrears_amount:float}>  $arrearsLeases
     * @param  array<string, array{
     *     currency:string,
     *     scheduledDue:float,
     *     scheduledPaid:float,
     *     arrears:float,
     *     contractBalance:float
     * }>  $currencyTotals
     */
    public function __construct(
        public float $scheduledDue,
        public float $scheduledPaid,
        public float $arrears,
        public float $contractBalance,
        public int $activeLeases,
        public Collection $arrearsLeases,
        public array $currencyTotals,
    ) {}
}
