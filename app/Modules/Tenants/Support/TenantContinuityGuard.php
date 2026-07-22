<?php

namespace App\Modules\Tenants\Support;

use App\Models\TenantProfile;
use Illuminate\Validation\ValidationException;

final class TenantContinuityGuard
{
    public function ensureStatusCanChange(TenantProfile $tenant, string $status): void
    {
        if ($status !== 'active' && $tenant->status === 'active' && $this->hasActiveLease($tenant)) {
            throw ValidationException::withMessages([
                'status' => trans('app.errors.tenant_has_active_lease'),
            ]);
        }
    }

    public function canArchive(TenantProfile $tenant): bool
    {
        return ! $this->hasActiveLease($tenant);
    }

    private function hasActiveLease(TenantProfile $tenant): bool
    {
        return $tenant->leases()->where('status', 'active')->exists();
    }
}
