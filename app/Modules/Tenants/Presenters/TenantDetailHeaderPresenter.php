<?php

namespace App\Modules\Tenants\Presenters;

use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Tenants\Data\TenantDetailData;

final class TenantDetailHeaderPresenter
{
    /** @return array<string, mixed> */
    public function present(TenantDetailData $data): array
    {
        $tenant = $data->tenant;
        $leasesEnabled = PortfolioModules::enabledForUser($data->actor, 'leases');
        $paymentsEnabled = PortfolioModules::enabledForUser($data->actor, 'payments');
        $canCreateLease = $leasesEnabled && $tenant->status === 'active';
        $canRecordPayment = $paymentsEnabled && $data->payableLease !== null;
        $actions = [];

        if ($canRecordPayment) {
            $actions[] = [
                'label' => trans('app.tenants.record_payment'),
                'href' => route('payments.create', ['lease_id' => $data->payableLease->id]),
                'variant' => 'primary',
            ];
        } elseif ($canCreateLease) {
            $actions[] = [
                'label' => trans('app.tenants.create_lease'),
                'href' => route('leases.create', ['tenant_profile_id' => $tenant->id]),
                'variant' => 'primary',
            ];
        }

        $actions[] = [
            'label' => trans('app.tenants.edit_tenant'),
            'href' => route('tenants.edit', $tenant),
            'variant' => ($canRecordPayment || $canCreateLease) ? 'secondary' : 'primary',
        ];

        if ($canRecordPayment && $canCreateLease) {
            $actions[] = [
                'label' => trans('app.tenants.create_lease'),
                'href' => route('leases.create', ['tenant_profile_id' => $tenant->id]),
                'variant' => 'secondary',
            ];
        }

        return [
            'eyebrow' => trans('app.tenants.detail_eyebrow'),
            'title' => filled($tenant->user?->name)
                ? $tenant->user->name
                : ($tenant->company_name ?: trans('app.tenants.tenant_number', ['id' => $tenant->id])),
            'description' => trans('app.tenants.detail_description', [
                'profile' => trans("app.tenants.{$tenant->profile_type}"),
                'status' => trans("app.status.{$tenant->status}"),
            ]),
            'backHref' => route('tenants.index'),
            'backLabel' => trans('app.tenants.all_tenants'),
            'actions' => $actions,
        ];
    }
}
