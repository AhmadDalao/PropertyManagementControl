<?php

namespace App\Modules\Users\Actions;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Users\Support\UserAccess;
use App\Modules\Users\Support\UserContinuityGuard;
use App\Modules\Users\Support\UserInputGuard;
use App\Modules\Users\Support\UserPortfolioOwnership;
use App\Modules\Users\Support\UserRoleGuard;
use App\Modules\Users\Support\UserSessionRevoker;
use App\Modules\Users\Support\UserTenantProfileSynchronizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class UpdateUser
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly UserInputGuard $input,
        private readonly UserRoleGuard $roles,
        private readonly UserContinuityGuard $continuity,
        private readonly UserPortfolioOwnership $ownership,
        private readonly UserTenantProfileSynchronizer $tenants,
        private readonly UserSessionRevoker $sessions,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(User $actor, User $target, array $data): User
    {
        $this->access->ensureCanManage($actor, $target);
        $data['email'] ??= $target->email;
        $this->input->validate($data, passwordRequired: false);

        return DB::transaction(function () use ($actor, $target, $data): User {
            $user = User::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $user->loadMissing(['roles', 'tenantProfile']);
            $this->access->ensureCanManage($actor, $user);
            $previousRole = $user->getRoleNames()->first();
            $previousEmail = $user->email;
            $role = (string) ($data['role'] ?? '');
            $status = (string) $data['status'];
            $this->roles->ensureAssignable($actor, $role, $user);
            $this->continuity->ensureUpdateAllowed($user, $role, $status);

            $credentialsChanged = $this->updateAttributes($user, $data);
            $user->syncRoles([$role]);
            $portfolio = $this->lockedPortfolio($user);
            $this->ownership->claim($portfolio, $user, $role, $previousRole !== 'owner');
            $this->tenants->sync($user, $role, $status);

            if ($credentialsChanged || $status !== 'active') {
                $this->sessions->revoke($user, $previousEmail);
            }

            return $user->refresh()->load(['portfolio', 'roles', 'tenantProfile']);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    private function updateAttributes(User $user, array $data): bool
    {
        $email = mb_strtolower(trim((string) $data['email']));
        $emailChanged = $email !== mb_strtolower($user->email);
        $passwordChanged = filled($data['password'] ?? null);
        $updates = [
            'name' => trim((string) $data['name']),
            'email' => $email,
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
            'preferred_locale' => $data['preferred_locale'],
            'status' => $data['status'],
        ];

        if ($emailChanged) {
            $updates['email_verified_at'] = null;
        }

        if ($passwordChanged) {
            $updates['password'] = Hash::make((string) $data['password']);
            $updates['force_password_reset'] = true;
        }

        $user->forceFill($updates)->save();

        return $emailChanged || $passwordChanged;
    }

    private function lockedPortfolio(User $user): ?Portfolio
    {
        return $user->portfolio_id
            ? Portfolio::query()->lockForUpdate()->whereKey($user->portfolio_id)->first()
            : null;
    }
}
