<?php

namespace App\Modules\Authentication\Actions;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ResetUserPassword
{
    /** @param array{token:string,email:string,password:string,password_confirmation:string} $data */
    public function execute(array $data): string
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'force_password_reset' => false,
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => trans($status),
            ]);
        }

        return trans($status);
    }
}
