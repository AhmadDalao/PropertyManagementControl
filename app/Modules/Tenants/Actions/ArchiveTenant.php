<?php

namespace App\Modules\Tenants\Actions;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Tenants\Support\TenantAccess;
use App\Modules\Tenants\Support\TenantContinuityGuard;
use App\Modules\Tenants\Support\TenantPortalAccountManager;
use Illuminate\Support\Facades\DB;

final class ArchiveTenant
{
    public function __construct(
        private readonly TenantAccess $access,
        private readonly TenantContinuityGuard $continuity,
        private readonly TenantPortalAccountManager $accounts,
    ) {}

    public function handle(User $actor, TenantProfile $tenant): bool
    {
        $this->access->ensureCanManage($actor, $tenant);

        return DB::transaction(function () use ($actor, $tenant): bool {
            $lockedTenant = TenantProfile::query()->lockForUpdate()->whereKey($tenant->id)->firstOrFail();
            $this->access->ensureCanManage($actor, $lockedTenant);

            if (! $this->continuity->canArchive($lockedTenant)) {
                return false;
            }

            $lockedTenant->update(['status' => 'blocked']);
            $this->accounts->suspend($lockedTenant);

            return true;
        }, attempts: 3);
    }
}
