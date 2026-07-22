<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;

final class UserTableRowPresenter
{
    /** @return array<string, mixed> */
    public function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'force_password_reset' => $user->force_password_reset,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'is_showcase' => $user->showcase_dataset_id !== null,
            'open_assignments_count' => (int) ($user->getAttribute('open_assignments_count') ?? 0),
            'roles' => $user->roles->pluck('name')->values()->all(),
            'portfolio' => $user->portfolio ? [
                'id' => $user->portfolio->id,
                'name_en' => $user->portfolio->name_en,
                'name_ar' => $user->portfolio->name_ar,
                'code' => $user->portfolio->code,
            ] : null,
        ];
    }
}
