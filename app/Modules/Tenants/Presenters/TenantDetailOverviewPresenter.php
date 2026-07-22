<?php

namespace App\Modules\Tenants\Presenters;

use App\Modules\Shared\ResourcePresenter;
use App\Modules\Tenants\Data\TenantDetailData;

final class TenantDetailOverviewPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<string, mixed> */
    public function present(TenantDetailData $data): array
    {
        $tenant = $data->tenant;
        $user = $tenant->user_id ? $tenant->user : null;
        $lease = $data->activeLease;
        $currency = $lease ? $lease->currency : 'SAR';
        $paid = $lease ? (float) $lease->total_paid : 0.0;

        return [
            'decisionCards' => [
                [
                    'title' => trans('app.tenants.portal_account'),
                    'value' => trans('app.status.'.($user ? $user->status : 'inactive')),
                    'detail' => $user ? $user->email : trans('app.tenants.no_login_account'),
                    'tone' => $user?->status === 'active' ? 'teal' : 'danger',
                    'icon' => 'bi-person-lock',
                ],
                [
                    'title' => trans('app.tenants.current_rental'),
                    'value' => $lease ? $lease->code : trans('app.tenants.no_active_lease'),
                    'detail' => $this->resources->localized(
                        $lease?->leaseable?->getAttribute('title_en'),
                        $lease?->leaseable?->getAttribute('title_ar'),
                    ),
                    'href' => $lease
                        ? route('leases.show', $lease)
                        : route('leases.create', ['tenant_profile_id' => $tenant->id]),
                    'actionLabel' => $lease
                        ? trans('app.tenants.open_lease')
                        : trans('app.tenants.create_lease'),
                    'tone' => $lease ? 'primary' : 'muted',
                    'icon' => 'bi-file-earmark-text',
                ],
                [
                    'title' => trans('app.tenants.contract_balance'),
                    'value' => $lease
                        ? number_format((float) $lease->balance_remaining, 2).' '.$lease->currency
                        : '0.00 SAR',
                    'detail' => trans('app.tenants.total_paid_value', [
                        'amount' => number_format($paid, 2),
                        'currency' => $currency,
                    ]),
                    'tone' => $lease && $lease->balance_remaining > 0 ? 'danger' : 'teal',
                    'icon' => 'bi-wallet2',
                ],
                [
                    'title' => trans('app.tenants.open_maintenance'),
                    'value' => $data->openMaintenanceCount,
                    'detail' => trans('app.tenants.recent_requests_count', [
                        'count' => $data->maintenance->count(),
                    ]),
                    'tone' => $data->openMaintenanceCount > 0 ? 'danger' : 'muted',
                    'icon' => 'bi-tools',
                ],
            ],
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.tenants.status'), 'value' => trans("app.status.{$tenant->status}"), 'tone' => $tenant->status === 'active' ? 'teal' : 'muted'],
                ['label' => trans('app.tenants.active_leases_label'), 'value' => $data->activeLeaseCount, 'tone' => 'primary'],
                ['label' => trans('app.tenants.paid'), 'value' => number_format($paid, 2).' '.$currency],
                ['label' => trans('app.tenants.open_maintenance'), 'value' => $data->openMaintenanceCount, 'tone' => $data->openMaintenanceCount > 0 ? 'danger' : 'muted'],
            ]),
            'sections' => $this->sections($data, $paid, $currency),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sections(TenantDetailData $data, float $paid, string $currency): array
    {
        $tenant = $data->tenant;
        $user = $tenant->user;
        $lease = $data->activeLease;

        return [
            [
                'title' => trans('app.tenants.profile_section'),
                'description' => trans('app.tenants.profile_section_help'),
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.tenants.email'), 'value' => $user?->email],
                    ['label' => trans('app.tenants.phone'), 'value' => $user?->phone],
                    ['label' => trans('app.tenants.portal_language'), 'value' => trans('app.tenants.'.($user?->preferred_locale === 'ar' ? 'arabic' : 'english'))],
                    ['label' => trans('app.tenants.portfolio'), 'value' => $this->resources->localized($tenant->portfolio?->name_en, $tenant->portfolio?->name_ar), 'href' => $tenant->portfolio ? route('portfolios.show', $tenant->portfolio) : null],
                    ['label' => trans('app.tenants.profile_type'), 'value' => trans("app.tenants.{$tenant->profile_type}")],
                    ['label' => trans('app.tenants.national_id'), 'value' => $tenant->national_id],
                    ['label' => trans('app.tenants.company_name'), 'value' => $tenant->company_name],
                    ['label' => trans('app.tenants.emergency_contact'), 'value' => trim(($tenant->emergency_contact_name ?? '').' '.($tenant->emergency_contact_phone ?? ''))],
                    ['label' => trans('app.tenants.address'), 'value' => $tenant->address],
                    ['label' => trans('app.tenants.notes'), 'value' => $tenant->notes],
                ]),
            ],
            [
                'title' => trans('app.tenants.financial_position'),
                'description' => trans('app.tenants.financial_position_help'),
                'tab' => 'financial',
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.tenants.active_contract'), 'value' => $lease?->code, 'href' => $lease ? route('leases.show', $lease) : null],
                    ['label' => trans('app.tenants.contract_ends'), 'value' => $lease?->ends_at?->toDateString()],
                    ['label' => trans('app.tenants.total_paid'), 'value' => number_format($paid, 2).' '.$currency],
                    ['label' => trans('app.tenants.contract_balance'), 'value' => $lease ? number_format((float) $lease->balance_remaining, 2).' '.$lease->currency : null],
                    ['label' => trans('app.tenants.last_payment'), 'value' => $data->lastPayment?->received_on?->toDateString()],
                ]),
            ],
        ];
    }
}
