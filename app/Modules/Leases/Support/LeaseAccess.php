<?php

namespace App\Modules\Leases\Support;

use App\Models\Lease;
use App\Models\User;

final class LeaseAccess
{
    public function canManageSection(User $actor): bool
    {
        return $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']);
    }

    public function canManage(User $actor, Lease $lease): bool
    {
        return $this->canManageSection($actor)
            && ($actor->hasRole('superadmin') || $actor->portfolio_id === $lease->portfolio_id);
    }

    public function canAccess(User $actor, Lease $lease): bool
    {
        if ($this->canManage($actor, $lease)) {
            return true;
        }

        return $actor->hasRole('tenant')
            && $lease->tenantProfile()->where('user_id', $actor->id)->exists();
    }

    public function ensureCanAccess(User $actor, Lease $lease): void
    {
        abort_unless(
            $this->canAccess($actor, $lease),
            403,
            trans('app.errors.lease_access_denied')
        );
    }

    public function ensureCanManage(User $actor, Lease $lease): void
    {
        abort_unless(
            $this->canManage($actor, $lease),
            403,
            trans('app.errors.portfolio_access_denied')
        );
    }

    public function ensureManager(User $actor): void
    {
        abort_unless(
            $this->canManageSection($actor),
            403,
            trans('app.errors.section_access_denied')
        );
    }
}
