<?php

namespace App\Modules\Tenants\Presenters;

use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Tenants\Data\TenantDetailData;

final class TenantDetailOverviewPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<string, mixed> */
    public function present(TenantDetailData $data): array
    {
        return [
            'stats' => $this->resources->detailItems($this->stats($data)),
            'sections' => $this->sections($data),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function stats(TenantDetailData $data): array
    {
        $tenant = $data->tenant;
        $user = $tenant->user;
        $lease = $data->activeLease;
        $stats = [
            [
                'label' => trans('app.tenants.profile_status'),
                'value' => trans("app.status.{$tenant->status}"),
                'tone' => $tenant->status === 'active' ? 'teal' : 'danger',
            ],
            [
                'label' => trans('app.tenants.portal_status'),
                'value' => trans('app.status.'.($user->status ?? 'inactive')),
                'tone' => $user?->status === 'active' ? 'teal' : 'danger',
            ],
        ];

        if (PortfolioModules::enabledForUser($data->actor, 'leases')) {
            $stats[] = [
                'label' => trans('app.tenants.current_contract'),
                'value' => $lease->code ?? trans('app.tenants.no_active_lease'),
                'tone' => $lease ? 'primary' : 'muted',
            ];
        }

        if (PortfolioModules::enabledForUser($data->actor, 'payments')) {
            $stats[] = [
                'label' => trans('app.tenants.total_paid'),
                'value' => $lease ? $this->money($lease->total_paid, $lease->currency) : '-',
            ];
            $stats[] = [
                'label' => trans('app.tenants.outstanding'),
                'value' => $lease ? $this->money($lease->balance_remaining, $lease->currency) : '-',
                'tone' => $lease && $lease->balance_remaining > 0 ? 'danger' : 'teal',
            ];
        }

        if (PortfolioModules::enabledForUser($data->actor, 'maintenance')) {
            $stats[] = [
                'label' => trans('app.tenants.open_maintenance'),
                'value' => $data->openMaintenanceCount,
                'tone' => $data->openMaintenanceCount > 0 ? 'danger' : 'teal',
            ];
        }

        return $stats;
    }

    /** @return array<int, array<string, mixed>> */
    private function sections(TenantDetailData $data): array
    {
        $sections = [$this->profileSection($data)];

        if (PortfolioModules::enabledForUser($data->actor, 'leases')) {
            $sections[] = $this->rentalSection($data);
        }

        if (PortfolioModules::enabledForUser($data->actor, 'payments')) {
            $sections[] = $this->financialSection($data);
        }

        return $sections;
    }

    /** @return array<string, mixed> */
    private function profileSection(TenantDetailData $data): array
    {
        $tenant = $data->tenant;
        $user = $tenant->user;

        return [
            'key' => 'profile',
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
        ];
    }

    /** @return array<string, mixed> */
    private function rentalSection(TenantDetailData $data): array
    {
        $lease = $data->activeLease;

        return [
            'key' => 'rental',
            'title' => trans('app.tenants.rental_section'),
            'description' => trans('app.tenants.rental_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.tenants.current_contract'), 'value' => $lease?->code, 'href' => $lease ? route('leases.show', $lease) : null],
                ['label' => trans('app.tenants.property_unit'), 'value' => $this->resources->localized($lease?->leaseable?->getAttribute('title_en'), $lease?->leaseable?->getAttribute('title_ar')), 'href' => $lease?->leaseable ? route('assets.show', $lease->leaseable) : null],
                ['label' => trans('app.tenants.lease_starts'), 'value' => $lease?->started_at?->toDateString()],
                ['label' => trans('app.tenants.contract_ends'), 'value' => $lease?->ends_at?->toDateString()],
                ['label' => trans('app.tenants.days_left'), 'value' => $lease?->days_remaining !== null ? trans('app.tenants.days_remaining_value', ['count' => max(0, $lease->days_remaining)]) : null],
                ['label' => trans('app.leases.rent_amount'), 'value' => $lease ? $this->money((float) $lease->rent_amount, $lease->currency) : null],
                ['label' => trans('app.leases.deposit'), 'value' => $lease ? $this->money((float) $lease->deposit_amount, $lease->currency) : null],
                ['label' => trans('app.leases.frequency'), 'value' => $lease ? trans("app.leases.frequency_{$lease->payment_frequency}") : null],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private function financialSection(TenantDetailData $data): array
    {
        $lease = $data->activeLease;

        return [
            'key' => 'financial',
            'title' => trans('app.tenants.financial_position'),
            'description' => trans('app.tenants.financial_position_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.tenants.total_paid'), 'value' => $lease ? $this->money($lease->total_paid, $lease->currency) : null],
                ['label' => trans('app.tenants.contract_balance'), 'value' => $lease ? $this->money($lease->balance_remaining, $lease->currency) : null],
                ['label' => trans('app.tenants.overdue'), 'value' => $lease ? $this->money($lease->overdue_amount, $lease->currency) : null],
                ['label' => trans('app.tenants.next_due'), 'value' => $lease?->next_due_installment?->due_date?->toDateString()],
                ['label' => trans('app.tenants.last_payment'), 'value' => $data->lastPayment?->received_on?->toDateString()],
            ]),
        ];
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }
}
