<?php

namespace App\Modules\Tenants\Presenters;

use App\Modules\Tenants\Data\TenantFormData;

final class TenantFormValuesPresenter
{
    /** @return array<string, mixed> */
    public function present(TenantFormData $data): array
    {
        $tenant = $data->tenant;

        if (! $tenant) {
            return [
                'portfolio_id' => (string) ($data->defaults['portfolio_id'] ?? $data->actor->portfolio_id ?? ''),
                'name' => '',
                'email' => '',
                'password' => '',
                'phone' => '',
                'preferred_locale' => 'en',
                'status' => 'active',
                'profile_type' => 'individual',
                'national_id' => '',
                'company_name' => '',
                'address' => '',
                'emergency_contact_name' => '',
                'emergency_contact_phone' => '',
                'notes' => '',
            ];
        }

        $user = $tenant->user_id ? $tenant->user : null;

        return [
            'portfolio_id' => (string) $tenant->portfolio_id,
            'name' => $user ? $user->name : '',
            'email' => $user ? $user->email : '',
            'password' => '',
            'phone' => $user ? ($user->phone ?? '') : '',
            'preferred_locale' => $user ? $user->preferred_locale : 'en',
            'status' => $tenant->status,
            'profile_type' => $tenant->profile_type,
            'national_id' => $tenant->national_id ?? '',
            'company_name' => $tenant->company_name ?? '',
            'address' => $tenant->address ?? '',
            'emergency_contact_name' => $tenant->emergency_contact_name ?? '',
            'emergency_contact_phone' => $tenant->emergency_contact_phone ?? '',
            'notes' => $tenant->notes ?? '',
        ];
    }
}
