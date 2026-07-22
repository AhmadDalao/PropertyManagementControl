<?php

namespace App\Modules\Users\Support;

use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class UserTenantProfileSynchronizer
{
    public function sync(User $user, string $role, string $status): void
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

        TenantProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'portfolio_id' => $user->portfolio_id,
                'profile_type' => 'individual',
                'status' => UserOptions::tenantProfileStatus($status),
            ],
        );
    }
}
