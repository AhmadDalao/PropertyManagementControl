<?php

namespace App\Modules\Leases\Presenters;

use App\Models\LeaseInstallment;
use Carbon\CarbonInterface;

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
            'start' => $this->arabicDate($installment->period_start),
            'end' => $this->arabicDate($installment->period_end),
        ]);
    }

    private function arabicDate(?CarbonInterface $date): string
    {
        if (! $date) {
            return '-';
        }

        return strtr($date->copy()->locale('ar')->translatedFormat('j F Y'), [
            '0' => '٠',
            '1' => '١',
            '2' => '٢',
            '3' => '٣',
            '4' => '٤',
            '5' => '٥',
            '6' => '٦',
            '7' => '٧',
            '8' => '٨',
            '9' => '٩',
        ]);
    }
}
