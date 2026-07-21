<?php

namespace App\Modules\Profile\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateProfilePassword
{
    public function execute(User $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'force_password_reset' => false,
        ])->save();
    }
}
