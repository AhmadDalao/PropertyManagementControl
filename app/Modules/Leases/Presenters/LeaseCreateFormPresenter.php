<?php

namespace App\Modules\Leases\Presenters;

use App\Modules\Leases\Data\LeaseFormData;

final class LeaseCreateFormPresenter
{
    public function __construct(private readonly LeaseFormFieldsPresenter $fields) {}

    /** @return array<string, mixed> */
    public function present(LeaseFormData $data): array
    {
        $tenantId = $this->selected($data->defaults['tenant_profile_id'] ?? null, $data->tenants);
        $assetId = $this->selected($data->defaults['asset_id'] ?? null, $data->assets);

        return [
            'title' => trans('app.leases.create_lease'),
            'description' => trans('app.leases.create_description'),
            'backHref' => route('leases.index'),
            'backLabel' => trans('app.leases.all_leases'),
            'action' => route('leases.store'),
            'method' => 'post',
            'submitLabel' => trans('app.leases.create_lease'),
            'fields' => $this->fields->create($data),
            'initialValues' => [
                'portfolio_id' => (string) ($data->portfolioId ?? ''),
                'tenant_profile_id' => (string) $tenantId,
                'asset_id' => (string) $assetId,
                'renewed_from_lease_id' => (string) ($data->defaults['renewed_from_lease_id'] ?? ''),
                'status' => $data->defaults['status'] ?? 'active',
                'payment_frequency' => $data->defaults['payment_frequency'] ?? 'monthly',
                'started_at' => $data->defaults['started_at'] ?? now()->toDateString(),
                'ends_at' => $data->defaults['ends_at'] ?? now()->addYear()->toDateString(),
                'signed_at' => $data->defaults['signed_at'] ?? '',
                'rent_amount' => $data->defaults['rent_amount'] ?? 0,
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
    private function selected(mixed $requested, array $options): int|string
    {
        $id = filter_var($requested, FILTER_VALIDATE_INT);

        return $id && collect($options)->contains('value', $id)
            ? (int) $id
            : ($options[0]['value'] ?? '');
    }
}
