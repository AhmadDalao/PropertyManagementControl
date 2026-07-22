<?php

namespace App\Modules\Users\Actions;

use App\Models\User;
use App\Modules\Users\Support\UserAccess;
use App\Modules\Users\Support\UserInputGuard;
use App\Modules\Users\Support\UserPortfolioOwnership;
use App\Modules\Users\Support\UserPortfolioResolver;
use App\Modules\Users\Support\UserRoleGuard;
use App\Modules\Users\Support\UserTenantProfileSynchronizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class CreateUser
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly UserInputGuard $input,
        private readonly UserRoleGuard $roles,
        private readonly UserPortfolioResolver $portfolios,
        private readonly UserPortfolioOwnership $ownership,
        private readonly UserTenantProfileSynchronizer $tenants,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(User $actor, array $data): User
    {
        $this->access->ensureManager($actor);
        $this->input->validate($data, passwordRequired: true);
        $role = (string) ($data['role'] ?? '');
        $this->roles->ensureAssignable($actor, $role);

        return DB::transaction(function () use ($actor, $data, $role): User {
            $portfolio = $this->portfolios->resolve($actor, $data['portfolio_id'] ?? null, $role);
            $user = User::query()->create([
                'portfolio_id' => $portfolio?->id,
                'name' => trim((string) $data['name']),
                'email' => mb_strtolower(trim((string) $data['email'])),
                'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
                'preferred_locale' => $data['preferred_locale'],
                'status' => $data['status'],
                'force_password_reset' => true,
                'password' => Hash::make((string) $data['password']),
            ]);

            $user->syncRoles([$role]);
            $this->ownership->claim($portfolio, $user, $role);
            $this->tenants->sync($user, $role, (string) $data['status']);

            return $user->load(['portfolio', 'roles', 'tenantProfile']);
        }, attempts: 3);
    }
}
