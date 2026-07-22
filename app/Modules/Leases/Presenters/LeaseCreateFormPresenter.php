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
                'status' => 'active',
                'payment_frequency' => 'monthly',
                'started_at' => now()->toDateString(),
                'ends_at' => now()->addYear()->toDateString(),
                'signed_at' => '',
                'rent_amount' => 0,
                'deposit_amount' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'currency' => 'SAR',
                'billing_day' => 1,
                'terms_en' => '',
                'terms_ar' => '',
                'notes' => '',
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
