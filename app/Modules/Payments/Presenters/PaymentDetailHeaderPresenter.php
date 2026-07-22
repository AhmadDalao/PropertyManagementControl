<?php

namespace App\Modules\Payments\Presenters;

use App\Modules\Payments\Data\PaymentDetailData;

final class PaymentDetailHeaderPresenter
{
    /** @return array<string, mixed> */
    public function present(PaymentDetailData $data): array
    {
        $payment = $data->payment;
        $actions = [];

        if ($data->adminMode) {
            $actions[] = [
                'label' => trans('app.payments.review_payment'),
                'href' => route('payments.edit', $payment),
                'variant' => 'primary',
            ];
        }

        if ($payment->status === 'posted') {
            $actions[] = [
                'label' => trans('app.payments.download_receipt'),
                'href' => route('payments.receipt', $payment),
                'variant' => 'secondary',
            ];
        }

        if ($payment->lease) {
            $actions[] = [
                'label' => trans('app.payments.open_lease'),
                'href' => route('leases.show', $payment->lease),
                'variant' => 'secondary',
            ];
        }

        return [
            'eyebrow' => trans('app.payments.payment_detail'),
            'title' => $payment->reference ?: trans('app.payments.payment_number', ['id' => $payment->id]),
            'description' => trans('app.payments.detail_description', [
                'status' => trans("app.status.{$payment->status}"),
                'method' => trans("app.payments.method_{$payment->method}"),
                'type' => trans("app.payments.type_{$payment->type}"),
            ]),
            'backHref' => $data->adminMode ? route('payments.index') : route('dashboard'),
            'backLabel' => $data->adminMode
                ? trans('app.payments.all_payments')
                : trans('app.nav.dashboard'),
            'actions' => $actions,
        ];
    }
}
