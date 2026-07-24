<?php

namespace App\Modules\LeaseRenewals\Support;

use App\Models\Lease;
use Illuminate\Database\Eloquent\Builder;

final class LeaseRenewalNoticeScope
{
    /** @param Builder<Lease> $query */
    public function apply(Builder $query, bool $due): void
    {
        $operator = $due ? '<=' : '>';
        $today = today()->toDateString();

        if ($query->getModel()->getConnection()->getDriverName() === 'sqlite') {
            $query->whereRaw(
                "date(ends_at, '-' || renewal_notice_days || ' days') {$operator} date(?)",
                [$today],
            );

            return;
        }

        $query->whereRaw(
            "DATE_SUB(ends_at, INTERVAL renewal_notice_days DAY) {$operator} ?",
            [$today],
        );
    }
}
