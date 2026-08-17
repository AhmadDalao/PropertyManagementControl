<?php

namespace App\Modules\Tenants\Presenters;

use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Tenants\Data\TenantDetailData;

final class TenantWorkflowPresenter
{
    /** @return array<string, mixed> */
    public function present(TenantDetailData $data): array
    {
        $tenant = $data->tenant;
        $user = $tenant->user;
        $lease = $data->activeLease;

        if (! $user || $user->status !== 'active') {
            return $this->workflow(
                'restore_portal_access',
                $user ? trans("app.status.{$user->status}") : trans('app.tenants.no_login_account'),
                'danger',
                'bi-person-lock',
                [[
                    'label' => trans($user ? 'app.users.manage_portal_access' : 'app.tenants.edit_tenant'),
                    'href' => $user
                        ? route('users.portal-access.show', ['user' => $user, 'origin' => 'tenant'])
                        : route('tenants.edit', $tenant),
                    'variant' => 'primary',
                ]],
            );
        }

        if (! $lease && PortfolioModules::enabledForUser($data->actor, 'leases')) {
            return $this->workflow(
                'start_first_lease',
                trans('app.tenants.no_active_lease'),
                'primary',
                'bi-file-earmark-plus',
                [[
                    'label' => trans('app.tenants.create_lease'),
                    'href' => route('leases.create', ['tenant_profile_id' => $tenant->id]),
                    'variant' => 'primary',
                ]],
            );
        }

        if ($lease && $lease->overdue_amount > 0 && PortfolioModules::enabledForUser($data->actor, 'payments')) {
            return $this->workflow(
                'collect_overdue_balance',
                $this->money($lease->overdue_amount, $lease->currency),
                'danger',
                'bi-exclamation-triangle',
                [[
                    'label' => trans('app.tenants.record_payment'),
                    'href' => route('payments.create', ['lease_id' => $lease->id]),
                    'variant' => 'primary',
                ], [
                    'label' => trans('app.tenants.open_lease'),
                    'href' => route('leases.show', ['lease' => $lease, 'tab' => 'installments']),
                    'variant' => 'secondary',
                ]],
            );
        }

        if ($data->openMaintenanceCount > 0 && PortfolioModules::enabledForUser($data->actor, 'maintenance')) {
            return $this->workflow(
                'resolve_open_service',
                trans('app.tenants.open_request_count', ['count' => $data->openMaintenanceCount]),
                'danger',
                'bi-tools',
                [[
                    'label' => trans('app.tenants.review_service'),
                    'href' => route('maintenance-requests.index', [
                        'tenant_profile_id' => $tenant->id,
                        'status' => 'open',
                    ]),
                    'variant' => 'primary',
                ]],
            );
        }

        if ($lease && $lease->days_remaining !== null && $lease->days_remaining <= 60) {
            return $this->workflow(
                'review_expiring_lease',
                trans('app.tenants.days_remaining_value', ['count' => max(0, $lease->days_remaining)]),
                'primary',
                'bi-calendar2-week',
                [[
                    'label' => trans('app.leases.prepare_renewal'),
                    'href' => route('leases.renew', $lease),
                    'variant' => 'primary',
                ]],
            );
        }

        if ($lease && $lease->balance_remaining > 0 && PortfolioModules::enabledForUser($data->actor, 'payments')) {
            return $this->workflow(
                'record_contract_payment',
                $this->money($lease->balance_remaining, $lease->currency),
                'primary',
                'bi-wallet2',
                [[
                    'label' => trans('app.tenants.record_payment'),
                    'href' => route('payments.create', ['lease_id' => $lease->id]),
                    'variant' => 'primary',
                ]],
            );
        }

        $statementEnabled = PortfolioModules::enabledForUser($data->actor, 'reports');

        return $this->workflow(
            'tenant_on_track',
            trans('app.tenants.no_immediate_account_risk'),
            'teal',
            'bi-check2-circle',
            [[
                'label' => trans($statementEnabled ? 'app.tenants.account_statement' : 'app.tenants.edit_tenant'),
                'href' => $statementEnabled
                    ? route('tenants.statement.show', $tenant)
                    : route('tenants.edit', $tenant),
                'variant' => 'secondary',
            ]],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $actions
     * @return array{
     *     eyebrow: string,
     *     title: string,
     *     description: string,
     *     status: string,
     *     tone: string,
     *     icon: string,
     *     actions: array<int, array<string, mixed>>
     * }
     */
    private function workflow(
        string $key,
        string $status,
        string $tone,
        string $icon,
        array $actions,
    ): array {
        return [
            'eyebrow' => trans('app.tenants.next_account_action'),
            'title' => trans("app.tenants.{$key}"),
            'description' => trans("app.tenants.{$key}_help"),
            'status' => $status,
            'tone' => $tone,
            'icon' => $icon,
            'actions' => $actions,
        ];
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }
}
