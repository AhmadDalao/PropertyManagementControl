<?php

namespace App\Modules\Leases\Presenters;

use App\Modules\Leases\Data\LeaseFormData;
use App\Modules\Portfolios\Support\PortfolioModules;

final class LeaseCreateFormPresenter
{
    public function __construct(private readonly LeaseFormFieldsPresenter $fields) {}

    /** @return array<string, mixed> */
    public function present(LeaseFormData $data): array
    {
        $tenantId = $this->requestedSelection($data->defaults['tenant_profile_id'] ?? null, $data->tenants);
        $assetId = $this->requestedSelection($data->defaults['asset_id'] ?? null, $data->assets);
        $onboarding = (string) ($data->defaults['onboarding'] ?? '') === '1';
        $tenantOnboardingQuery = array_filter([
            'next' => 'lease',
            'portfolio_id' => $data->portfolioId,
            'asset_id' => $this->requestedSelection($data->defaults['asset_id'] ?? null, $data->assets),
        ], fn (mixed $value): bool => $value !== null);

        return [
            'layout' => 'lease',
            'mode' => ! empty($data->defaults['renewed_from_lease_id']) ? 'renew' : 'create',
            'title' => trans($onboarding
                ? 'app.leases.start_tenancy'
                : 'app.leases.create_lease'),
            'description' => trans($onboarding
                ? 'app.leases.start_tenancy_description'
                : 'app.leases.create_description'),
            'backHref' => route('leases.index'),
            'backLabel' => trans('app.leases.all_leases'),
            'headerActions' => $data->portfolioId
                && PortfolioModules::enabledForUser($data->actor, 'tenants')
                ? [[
                    'label' => trans('app.leases.add_new_tenant'),
                    'href' => route('tenants.create', $tenantOnboardingQuery),
                    'variant' => 'secondary',
                ]]
                : [],
            'action' => route('leases.store'),
            'method' => 'post',
            'submitLabel' => trans($onboarding
                ? 'app.leases.create_draft_lease'
                : 'app.leases.create_lease'),
            'fields' => $this->fields->create($data),
            'initialValues' => [
                'portfolio_id' => (string) ($data->portfolioId ?? ''),
                'tenant_profile_id' => (string) ($tenantId ?? ''),
                'asset_id' => (string) ($assetId ?? ''),
                'renewed_from_lease_id' => (string) ($data->defaults['renewed_from_lease_id'] ?? ''),
                'status' => $data->defaults['status'] ?? 'draft',
                'payment_frequency' => $data->defaults['payment_frequency'] ?? 'monthly',
                'started_at' => $data->defaults['started_at'] ?? now()->toDateString(),
                'ends_at' => $data->defaults['ends_at'] ?? now()->addYear()->toDateString(),
                'signed_at' => $data->defaults['signed_at'] ?? '',
                'renewal_notice_days' => $data->defaults['renewal_notice_days'] ?? 30,
                'rent_amount' => $data->defaults['rent_amount'] ?? '',
                'deposit_amount' => $data->defaults['deposit_amount'] ?? 0,
                'tax_amount' => $data->defaults['tax_amount'] ?? 0,
                'discount_amount' => $data->defaults['discount_amount'] ?? 0,
                'currency' => $data->defaults['currency'] ?? 'SAR',
                'billing_day' => $data->defaults['billing_day'] ?? 1,
                'terms_en' => $data->defaults['terms_en'] ?? '',
                'terms_ar' => $data->defaults['terms_ar'] ?? '',
                'notes' => $data->defaults['notes'] ?? '',
            ],
        ];
    }

    /** @param array<int, array{value:int,label:string}> $options */
    private function requestedSelection(mixed $requested, array $options): ?int
    {
        $id = filter_var($requested, FILTER_VALIDATE_INT);

        return $id && collect($options)->contains('value', $id) ? (int) $id : null;
    }
}
