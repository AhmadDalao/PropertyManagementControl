<?php

namespace App\Modules\Leases\Presenters;

use App\Models\LeaseInstallment;

final class LeaseInstallmentLabelPresenter
{
    public function present(LeaseInstallment $installment): string
    {
        if (! app()->isLocale('ar')) {
            return (string) $installment->label;
        }

        if ($installment->line_type === 'deposit') {
            return trans('app.leases.security_deposit');
        }

        return trans('app.leases.rent_period', [
            'start' => $installment->period_start?->format('Y/m/d') ?? '-',
            'end' => $installment->period_end?->format('Y/m/d') ?? '-',
        ]);
    }
}
