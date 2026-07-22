<?php

namespace App\Modules\Users\Support;

use App\Models\User;
use App\Modules\Shared\AccountSessionRevoker;

final class UserSessionRevoker
{
    public function __construct(private readonly AccountSessionRevoker $sessions) {}

    public function revoke(User $user, ?string $previousEmail = null): void
    {
        $this->sessions->revoke($user, $previousEmail);
    }
}
