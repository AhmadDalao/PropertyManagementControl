<?php

namespace App\Modules\Tenants\Actions;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Tenants\Support\TenantAccess;
use App\Modules\Tenants\Support\TenantContinuityGuard;
use App\Modules\Tenants\Support\TenantInputGuard;
use App\Modules\Tenants\Support\TenantPortalAccountManager;
use App\Modules\Tenants\Support\TenantProfileAttributes;
use Illuminate\Support\Facades\DB;

final class UpdateTenant
{
    public function __construct(
        private readonly TenantAccess $access,
        private readonly TenantContinuityGuard $continuity,
        private readonly TenantInputGuard $input,
        private readonly TenantPortalAccountManager $accounts,
        private readonly TenantProfileAttributes $profiles,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, TenantProfile $tenant, array $data): TenantProfile
    {
        $this->access->ensureCanManage($actor, $tenant);

        return DB::transaction(function () use ($actor, $tenant, $data): TenantProfile {
            $lockedTenant = TenantProfile::query()->lockForUpdate()->whereKey($tenant->id)->firstOrFail();
            $this->access->ensureCanManage($actor, $lockedTenant);
            $lockedTenant->loadMissing('user');
            $data['email'] ??= $lockedTenant->user?->email;
            $this->input->validate($data, passwordRequired: ! $lockedTenant->user);
            $this->continuity->ensureStatusCanChange($lockedTenant, (string) $data['status']);
            $this->accounts->synchronize($lockedTenant, $data);
            $lockedTenant->update($this->profiles->from($data));

            return $lockedTenant->refresh()->load(['user', 'portfolio']);
        }, attempts: 3);
    }
}
