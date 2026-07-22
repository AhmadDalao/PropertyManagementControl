<?php

namespace App\Modules\Payments\Presenters;

use App\Modules\Payments\Data\PaymentDetailData;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

final class PaymentDetailOverviewPresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $userAccess,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function stats(PaymentDetailData $data): array
    {
        $payment = $data->payment;
        $allocated = (float) ($payment->getAttribute('allocations_sum_amount') ?? 0);
        $amount = (float) $payment->amount;

        return $this->resources->detailItems([
            ['label' => trans('app.payments.amount'), 'value' => $this->money($amount, $payment->currency), 'tone' => 'primary'],
            ['label' => trans('app.payments.allocated'), 'value' => $this->money($allocated, $payment->currency), 'tone' => 'teal'],
            ['label' => trans('app.payments.unallocated'), 'value' => $this->money(max(0, $amount - $allocated), $payment->currency)],
            ['label' => trans('app.payments.status'), 'value' => trans("app.status.{$payment->status}"), 'tone' => $payment->status === 'void' ? 'danger' : 'teal'],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(PaymentDetailData $data): array
    {
        $payment = $data->payment;
        $portfolioName = $this->resources->localized(
            $payment->portfolio?->name_en,
            $payment->portfolio?->name_ar,
        );
        $assetTitle = $this->resources->localized(
            $data->asset?->title_en,
            $data->asset?->title_ar,
        );

        return [[
            'title' => trans('app.payments.payment_record'),
            'description' => trans('app.payments.payment_record_help'),
            'items' => $this->resources->detailItems([
                [
                    'label' => trans('app.payments.tenant'),
                    'value' => $payment->tenantProfile?->user?->name,
                    'href' => $data->adminMode && $payment->tenantProfile
                        ? route('tenants.show', $payment->tenantProfile)
                        : null,
                ],
                [
                    'label' => trans('app.payments.lease'),
                    'value' => $payment->lease?->code,
                    'href' => $payment->lease ? route('leases.show', $payment->lease) : null,
                ],
                [
                    'label' => trans('app.payments.asset'),
                    'value' => $assetTitle,
                    'href' => $data->adminMode && $data->asset
                        ? route('assets.show', $data->asset)
                        : null,
                ],
                [
                    'label' => trans('app.payments.portfolio'),
                    'value' => $portfolioName,
                    'href' => $data->adminMode && $payment->portfolio
                        ? route('portfolios.show', $payment->portfolio)
                        : null,
                ],
                [
                    'label' => trans('app.payments.recorded_by'),
                    'value' => $data->adminMode ? $payment->recordedBy?->name : null,
                    'href' => $data->adminMode
                        ? $this->userAccess->recordHref($data->actor, $payment->recordedBy)
                        : null,
                ],
                ['label' => trans('app.payments.received_on'), 'value' => $payment->received_on?->toDateString()],
                ['label' => trans('app.payments.notes'), 'value' => $data->adminMode ? $payment->notes : null],
            ]),
        ]];
    }

    private function money(float $amount, ?string $currency): string
    {
        return number_format($amount, 2).' '.($currency ?: 'SAR');
    }
}
