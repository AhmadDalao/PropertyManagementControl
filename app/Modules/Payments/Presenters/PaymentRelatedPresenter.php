<?php

namespace App\Modules\Payments\Presenters;

use App\Models\PaymentAllocation;
use App\Modules\Payments\Data\PaymentDetailData;

final class PaymentRelatedPresenter
{
    /** @return array<int, array<string, mixed>> */
    public function present(PaymentDetailData $data): array
    {
        $payment = $data->payment;
        $installment = trans('app.payments.installment');
        $dueDate = trans('app.payments.due_date');
        $amount = trans('app.payments.amount');
        $count = (int) ($payment->getAttribute('allocations_count') ?? 0);

        return [[
            'title' => trans('app.payments.allocations'),
            'description' => $count > 50
                ? trans('app.payments.allocations_limited', ['shown' => 50, 'count' => $count])
                : trans('app.payments.allocations_help'),
            'columns' => [$installment, $dueDate, $amount],
            'rows' => $payment->allocations->map(fn (PaymentAllocation $allocation): array => [
                $installment => trans('app.payments.installment_number', [
                    'sequence' => data_get($allocation->leaseInstallment, 'sequence', '-'),
                ]),
                $dueDate => $allocation->leaseInstallment?->due_date?->toDateString(),
                $amount => number_format((float) $allocation->amount, 2).' '.$payment->currency,
            ])->all(),
            'emptyText' => trans('app.payments.no_allocations'),
        ]];
    }
}
