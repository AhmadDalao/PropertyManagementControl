<?php

namespace App\Modules\Users\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

final class UserRoleGuard
{
    public function ensureAssignable(User $actor, string $role, ?User $target = null): void
    {
        if (! in_array($role, UserOptions::assignableRoles($actor, $target), true)) {
            throw ValidationException::withMessages([
                'role' => trans('app.errors.invalid_role'),
            ]);
        }
    }
}
