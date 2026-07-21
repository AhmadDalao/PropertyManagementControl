<?php

namespace App\Modules\Profile\Presenters;

use App\Models\User;

class ProfilePresenter
{
    /** @return array<string, mixed> */
    public function present(User $user): array
    {
        $user->loadMissing(['portfolio', 'tenantProfile']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'preferred_locale' => $user->preferred_locale,
            'status' => $user->status,
            'force_password_reset' => $user->force_password_reset,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'roles' => $user->getRoleNames()->values()->all(),
            'portfolio' => $user->portfolio ? [
                'id' => $user->portfolio->id,
                'name_en' => $user->portfolio->name_en,
                'name_ar' => $user->portfolio->name_ar,
                'code' => $user->portfolio->code,
                'status' => $user->portfolio->status,
            ] : null,
            'tenant_profile' => $user->tenantProfile ? [
                'id' => $user->tenantProfile->id,
                'profile_type' => $user->tenantProfile->profile_type,
                'status' => $user->tenantProfile->status,
            ] : null,
        ];
    }
}
