<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Asset;
use App\Modules\Leases\Data\LeaseDetailData;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

final class LeaseDetailOverviewPresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $userAccess,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function stats(LeaseDetailData $data): array
    {
        $lease = $data->lease;

        return $this->resources->detailItems([
            ['label' => trans('app.leases.total_due'), 'value' => $this->money($lease->total_due, $lease->currency), 'tone' => 'primary'],
            ['label' => trans('app.leases.paid'), 'value' => $this->money($lease->total_paid, $lease->currency), 'tone' => 'teal'],
            ['label' => trans('app.leases.remaining'), 'value' => $this->money($lease->balance_remaining, $lease->currency), 'tone' => $lease->balance_remaining > 0 ? 'danger' : 'teal'],
            ['label' => trans('app.leases.days_left'), 'value' => $lease->days_remaining ?? trans('app.leases.ended')],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(LeaseDetailData $data): array
    {
        $lease = $data->lease;
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;

        return [
            [
                'title' => trans('app.leases.contract'),
                'description' => trans('app.leases.contract_help'),
                'tab' => 'overview',
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.leases.tenant'), 'value' => $lease->tenantProfile?->user?->name, 'href' => $data->adminMode && $lease->tenantProfile ? route('tenants.show', $lease->tenantProfile) : null],
                    ['label' => trans('app.leases.asset'), 'value' => $this->resources->localized($asset?->title_en, $asset?->title_ar), 'href' => $data->adminMode && $asset ? route('assets.show', $asset) : null],
                    ['label' => trans('app.leases.portfolio'), 'value' => $this->resources->localized($lease->portfolio?->name_en, $lease->portfolio?->name_ar), 'href' => $data->adminMode && $lease->portfolio ? route('portfolios.show', $lease->portfolio) : null],
                    ['label' => trans('app.leases.managed_by'), 'value' => $lease->managedBy?->name, 'href' => $data->adminMode ? $this->userAccess->recordHref($data->actor, $lease->managedBy) : null],
                    ['label' => trans('app.leases.started'), 'value' => $lease->started_at?->toDateString()],
                    ['label' => trans('app.leases.ends'), 'value' => $lease->ends_at?->toDateString()],
                    ['label' => trans('app.leases.signed'), 'value' => $lease->signed_at?->toDateString() ?? trans('app.leases.not_signed')],
                    ['label' => trans('app.leases.frequency'), 'value' => trans("app.leases.frequency_{$lease->payment_frequency}")],
                    ['label' => trans('app.leases.notes'), 'value' => $data->adminMode ? $lease->notes : null],
                ]),
            ],
            [
                'title' => trans('app.leases.amounts'),
                'description' => trans('app.leases.amounts_help'),
                'tab' => 'financial',
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.leases.rent_amount'), 'value' => $this->money($lease->rent_amount, $lease->currency)],
                    ['label' => trans('app.leases.deposit'), 'value' => $this->money($lease->deposit_amount, $lease->currency)],
                    ['label' => trans('app.leases.tax'), 'value' => $this->money($lease->tax_amount, $lease->currency)],
                    ['label' => trans('app.leases.discount'), 'value' => $this->money($lease->discount_amount, $lease->currency)],
                    ['label' => trans('app.leases.billing_day'), 'value' => $lease->billing_day],
                ]),
            ],
        ];
    }

    private function money(mixed $amount, string $currency): string
    {
        return number_format((float) $amount, 2).' '.$currency;
    }
}
