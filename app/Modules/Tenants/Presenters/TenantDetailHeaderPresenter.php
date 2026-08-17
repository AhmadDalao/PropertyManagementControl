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
        $actions = [[
            'label' => trans('app.tenants.edit_tenant'),
            'href' => route('tenants.edit', $tenant),
            'variant' => 'primary',
        ]];

        if (PortfolioModules::enabledForUser($data->actor, 'reports')
            && collect(['leases', 'payments', 'maintenance', 'documents'])->contains(
                fn (string $module): bool => PortfolioModules::enabledForUser($data->actor, $module),
            )) {
            $actions[] = [
                'label' => trans('app.tenants.account_statement'),
                'href' => route('tenants.statement.show', $tenant),
                'variant' => 'secondary',
            ];
        }

        if ($tenant->user) {
            $actions[] = [
                'label' => trans('app.users.manage_portal_access'),
                'href' => route('users.portal-access.show', [
                    'user' => $tenant->user,
                    'origin' => 'tenant',
                ]),
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
