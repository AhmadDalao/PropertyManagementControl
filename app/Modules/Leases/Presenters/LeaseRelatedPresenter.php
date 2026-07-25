<?php

namespace App\Modules\Leases\Presenters;

use App\Models\LeaseInstallment;
use App\Models\Payment;
use App\Modules\Leases\Data\LeaseDetailData;

final class LeaseRelatedPresenter
{
    public function __construct(private readonly LeaseInstallmentLabelPresenter $labels) {}

    /** @return array<int, array<string, mixed>> */
    public function present(LeaseDetailData $data): array
    {
        $lease = $data->lease;
        $installmentColumns = [
            trans('app.leases.sequence'),
            trans('app.leases.installment'),
            trans('app.leases.due_date'),
            trans('app.leases.status'),
            trans('app.leases.due'),
            trans('app.leases.paid'),
        ];

        if ($data->adminMode) {
            $installmentColumns[] = trans('app.rent_collection.follow_up_status');
        }

        return [
            [
                'title' => trans('app.leases.installments'),
                'description' => trans('app.leases.installments_help'),
                'columns' => $installmentColumns,
                'rows' => $lease->installments
                    ->map(function (LeaseInstallment $installment) use ($data): array {
                        $row = [
                            trans('app.leases.sequence') => $installment->sequence,
                            trans('app.leases.installment') => $this->labels->present($installment),
                            trans('app.leases.due_date') => $installment->due_date?->toDateString(),
                            trans('app.leases.status') => trans("app.status.{$installment->status}"),
                            trans('app.leases.due') => number_format((float) $installment->amount_due, 2),
                            trans('app.leases.paid') => number_format((float) $installment->amount_paid, 2),
                        ];

                        if ($data->adminMode) {
                            $row[trans('app.rent_collection.follow_up_status')] = $installment->remaining_amount > 0
                                ? [
                                    'label' => trans('app.rent_collection.manage_follow_up'),
                                    'href' => route('rent-collection.follow-up', $installment),
                                ]
                                : trans('app.rent_collection.follow_up_state_settled');
                        }

                        return $row;
                    })
                    ->all(),
                'emptyText' => trans('app.leases.no_installments'),
            ],
            [
                'title' => trans('app.leases.payments'),
                'description' => trans('app.leases.payments_help'),
                'columns' => [
                    trans('app.leases.payment'),
                    trans('app.leases.date'),
                    trans('app.leases.status'),
                    trans('app.leases.amount'),
                ],
                'rows' => $lease->payments->map(fn (Payment $payment): array => [
                    trans('app.leases.payment') => $payment->reference ?: '#'.$payment->id,
                    trans('app.leases.date') => $payment->received_on?->toDateString(),
                    trans('app.leases.status') => trans("app.status.{$payment->status}"),
                    trans('app.leases.amount') => number_format((float) $payment->amount, 2).' '.$payment->currency,
                ])->all(),
                'emptyText' => trans('app.leases.no_payments'),
                'actionHref' => $data->adminMode ? route('payments.create', ['lease_id' => $lease->id]) : null,
                'actionLabel' => $data->adminMode ? trans('app.leases.record_payment') : null,
            ],
        ];
    }
}
