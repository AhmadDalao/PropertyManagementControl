<?php

namespace App\Modules\Users\Support;

use App\Models\User;
use App\Modules\Shared\AccountContinuityGuard;

final class UserContinuityGuard
{
    public function __construct(private readonly AccountContinuityGuard $continuity) {}

    public function ensureUpdateAllowed(User $user, string $role, string $status): void
    {
        $this->continuity->ensureUpdateAllowed($user, $role, $status);
    }

    public function suspensionBlock(User $user): ?string
    {
        return $this->continuity->suspensionBlock($user);
    }
}
