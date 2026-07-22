<?php

namespace App\Modules\Users\Actions;

use App\Models\User;
use App\Modules\Users\Support\UserAccess;
use App\Modules\Users\Support\UserContinuityGuard;
use App\Modules\Users\Support\UserSessionRevoker;
use Illuminate\Support\Facades\DB;

final class SuspendUser
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly UserContinuityGuard $continuity,
        private readonly UserSessionRevoker $sessions,
    ) {}

    public function execute(User $actor, User $target): ?string
    {
        $this->access->ensureCanManage($actor, $target);

        return DB::transaction(function () use ($actor, $target): ?string {
            $user = User::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $user->loadMissing(['roles', 'tenantProfile']);
            $this->access->ensureCanManage($actor, $user);
            $blockingReason = $this->continuity->suspensionBlock($user);

            if ($blockingReason !== null) {
                return $blockingReason;
            }

            $user->update(['status' => 'suspended']);
            $user->tenantProfile?->update(['status' => 'blocked']);
            $this->sessions->revoke($user);

            return null;
        }, attempts: 3);
    }
}
