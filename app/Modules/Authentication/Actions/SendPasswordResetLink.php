<?php

namespace App\Modules\Authentication\Actions;

use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class SendPasswordResetLink
{
    public function execute(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => trans($status),
            ]);
        }

        return trans($status);
    }
}
