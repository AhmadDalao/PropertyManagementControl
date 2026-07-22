<?php

namespace App\Modules\Shared;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AccountSessionRevoker
{
    public function revoke(User $user, ?string $previousEmail = null): void
    {
        $user->setRememberToken(Str::random(60));
        $user->saveQuietly();

        DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();

        DB::table('password_reset_tokens')
            ->whereIn('email', array_values(array_unique(array_filter([
                $previousEmail,
                $user->email,
            ]))))
            ->delete();
    }
}
