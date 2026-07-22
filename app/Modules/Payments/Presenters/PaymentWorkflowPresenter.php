<?php

namespace App\Modules\Payments\Presenters;

use App\Modules\Payments\Data\PaymentDetailData;
use App\Support\PortfolioModules;

final class PaymentWorkflowPresenter
{
    /** @return array<string, mixed> */
    public function present(PaymentDetailData $data): array
    {
        $payment = $data->payment;

        return [
            'eyebrow' => trans('app.resource.next_step'),
            'title' => trans("app.payments.workflow_{$payment->status}_title"),
            'description' => trans("app.payments.workflow_{$payment->status}_description"),
            'status' => trans("app.status.{$payment->status}"),
            'tone' => match ($payment->status) {
                'posted' => 'teal',
                'void' => 'danger',
                default => 'primary',
            },
            'icon' => 'bi-receipt',
            'actions' => $data->adminMode
                ? $this->adminActions($data)
                : $this->tenantActions($data),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function adminActions(PaymentDetailData $data): array
    {
        $payment = $data->payment;
        $actions = [];

        if ($payment->status === 'pending') {
            $actions[] = [
                'label' => trans('app.payments.review_and_post'),
                'href' => route('payments.edit', $payment),
                'variant' => 'primary',
            ];
        }

        if ($payment->lease && PortfolioModules::enabledForUser($data->actor, 'leases')) {
            $actions[] = [
                'label' => trans('app.payments.open_lease'),
                'href' => route('leases.show', $payment->lease),
                'variant' => 'secondary',
            ];
        }

        if ($payment->status !== 'void') {
            $actions[] = [
                'label' => trans('app.payments.void'),
                'href' => route('payments.destroy', $payment),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.payments.void_confirm', [
                    'reference' => $payment->reference ?: '#'.$payment->id,
                ]),
            ];
        }

        return $actions;
    }

    /** @return array<int, array<string, mixed>> */
    private function tenantActions(PaymentDetailData $data): array
    {
        $payment = $data->payment;
        $actions = [];

        if ($payment->lease && PortfolioModules::enabledForUser($data->actor, 'leases')) {
            $actions[] = [
                'label' => trans('app.payments.open_lease'),
                'href' => route('leases.show', $payment->lease),
                'variant' => 'secondary',
            ];
        }

        return $actions;
    }
}
