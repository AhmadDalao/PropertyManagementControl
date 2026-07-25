<?php

namespace App\Modules\Users\Support;

use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class UserTenantProfileSynchronizer
{
    public function sync(User $user, string $role, string $status, ?User $actor = null): void
    {
        if ($role !== 'tenant') {
            $user->tenantProfile?->update(['status' => 'inactive']);

            return;
        }

        if ($user->portfolio_id === null) {
            throw ValidationException::withMessages([
                'role' => trans('app.errors.tenant_requires_portfolio'),
            ]);
        }

        $profile = TenantProfile::query()->firstOrNew(['user_id' => $user->id]);
        $profile->fill([
            'portfolio_id' => $user->portfolio_id,
            'profile_type' => 'individual',
            'status' => UserOptions::tenantProfileStatus($status),
        ]);

        if (! $profile->exists) {
            $profile->onboarded_by_user_id = $this->actorId($actor);
        }

        $profile->save();
    }

    /** @return positive-int|null */
    private function actorId(?User $actor): ?int
    {
        if (! $actor || $actor->id < 1) {
            return null;
        }

        return $actor->id;
    }
}
