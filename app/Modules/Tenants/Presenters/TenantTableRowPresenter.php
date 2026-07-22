<?php

namespace App\Modules\Tenants\Presenters;

use App\Models\TenantProfile;

final class TenantTableRowPresenter
{
    /** @return array<string, mixed> */
    public function present(TenantProfile $tenant): array
    {
        $missing = [];

        if ((bool) $tenant->getAttribute('missing_emergency')) {
            $missing[] = 'emergency_contact';
        }

        if ((bool) $tenant->getAttribute('missing_address')) {
            $missing[] = 'address';
        }

        if ($tenant->profile_type === 'company' && blank($tenant->company_name)) {
            $missing[] = 'company_name';
        }

        return [
            'id' => $tenant->id,
            'profile_type' => $tenant->profile_type,
            'national_id' => $tenant->national_id,
            'company_name' => $tenant->company_name,
            'status' => $tenant->status,
            'is_showcase' => $tenant->getIsShowcaseAttribute(),
            'missing_profile_fields' => $missing,
            'leases_count' => (int) ($tenant->getAttribute('leases_count') ?? 0),
            'active_leases_count' => (int) ($tenant->getAttribute('active_leases_count') ?? 0),
            'open_requests_count' => (int) ($tenant->getAttribute('open_requests_count') ?? 0),
            'user' => $tenant->user ? [
                'id' => $tenant->user->id,
                'name' => $tenant->user->name,
                'email' => $tenant->user->email,
                'phone' => $tenant->user->phone,
                'status' => $tenant->user->status,
            ] : null,
        ];
    }
}
