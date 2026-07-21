<?php

namespace App\Modules\Users\Actions;

use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Users\Support\UserAccess;
use App\Modules\Users\Support\UserOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ManageUsers
{
    public function __construct(private readonly UserAccess $access) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): User
    {
        $this->access->ensureManager($actor);
        $role = (string) $data['role'];
        $this->ensureAssignableRole($actor, $role);

        return DB::transaction(function () use ($actor, $data, $role): User {
            $portfolio = $this->resolvePortfolio($actor, $data['portfolio_id'] ?? null, $role);

            $user = User::query()->create([
                'portfolio_id' => $portfolio?->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'preferred_locale' => $data['preferred_locale'],
                'status' => $data['status'],
                'force_password_reset' => true,
                'password' => Hash::make((string) $data['password']),
            ]);

            $user->syncRoles([$role]);
            $this->claimPortfolioOwnership($portfolio, $user, $role);
            $this->syncTenantProfile($user, $role, (string) $data['status']);

            return $user->load(['portfolio', 'roles', 'tenantProfile']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, User $target, array $data): User
    {
        $this->access->ensureCanManage($actor, $target);

        return DB::transaction(function () use ($actor, $target, $data): User {
            $user = User::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $user->loadMissing('roles');
            $this->access->ensureCanManage($actor, $user);
            $previousRole = $user->getRoleNames()->first();
            $role = (string) $data['role'];
            $this->ensureAssignableRole($actor, $role, $user);
            $this->ensureOwnershipContinuity($user, $role, (string) $data['status']);
            $this->ensureTenantContinuity($user, $role, (string) $data['status']);

            $updates = [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'preferred_locale' => $data['preferred_locale'],
                'status' => $data['status'],
            ];

            if (filled($data['password'] ?? null)) {
                $updates['password'] = Hash::make((string) $data['password']);
                $updates['force_password_reset'] = true;
            }

            $user->update($updates);
            $user->syncRoles([$role]);

            $portfolio = $user->portfolio_id
                ? Portfolio::query()->lockForUpdate()->whereKey($user->portfolio_id)->first()
                : null;
            $this->claimPortfolioOwnership($portfolio, $user, $role, $previousRole !== 'owner');
            $this->syncTenantProfile($user, $role, (string) $data['status']);

            return $user->refresh()->load(['portfolio', 'roles', 'tenantProfile']);
        });
    }

    /**
     * Suspend an account without deleting audit history.
     *
     * @return string|null A translated blocking reason, or null after success.
     */
    public function suspend(User $actor, User $target): ?string
    {
        $this->access->ensureCanManage($actor, $target);

        return DB::transaction(function () use ($actor, $target): ?string {
            $user = User::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $user->loadMissing('roles');
            $this->access->ensureCanManage($actor, $user);

            if ($user->portfoliosOwned()->exists()) {
                return trans('app.errors.user_owns_portfolio');
            }

            if ($this->hasActiveTenantLease($user)) {
                return trans('app.errors.user_has_active_lease');
            }

            $user->update(['status' => 'suspended']);
            $user->tenantProfile?->update(['status' => 'blocked']);

            return null;
        });
    }

    private function ensureAssignableRole(User $actor, string $role, ?User $target = null): void
    {
        if (! in_array($role, UserOptions::assignableRoles($actor, $target), true)) {
            throw ValidationException::withMessages([
                'role' => trans('app.errors.invalid_role'),
            ]);
        }
    }

    private function resolvePortfolio(User $actor, mixed $requestedPortfolioId, string $role): ?Portfolio
    {
        if ($role === 'superadmin') {
            return null;
        }

        $portfolioId = filter_var(
            $requestedPortfolioId ?? $actor->portfolio_id,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if (! $portfolioId) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.role_requires_portfolio'),
            ]);
        }

        $this->access->ensurePortfolioAccess($actor, (int) $portfolioId);
        $portfolio = Portfolio::query()->lockForUpdate()->whereKey((int) $portfolioId)->firstOrFail();

        if ($portfolio->status !== 'active') {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.user_portfolio_inactive'),
            ]);
        }

        return $portfolio;
    }

    private function claimPortfolioOwnership(
        ?Portfolio $portfolio,
        User $user,
        string $role,
        bool $claim = true,
    ): void {
        if ($role !== 'owner' || ! $portfolio || ! $claim) {
            return;
        }

        if ($portfolio->status !== 'active') {
            throw ValidationException::withMessages([
                'role' => trans('app.errors.user_portfolio_inactive'),
            ]);
        }

        if ($portfolio->owner_user_id !== null && $portfolio->owner_user_id !== $user->id) {
            throw ValidationException::withMessages([
                'role' => trans('app.errors.portfolio_owner_exists'),
            ]);
        }

        if ($portfolio->owner_user_id === null) {
            $portfolio->update(['owner_user_id' => $user->id]);
        }
    }

    private function ensureOwnershipContinuity(User $user, string $role, string $status): void
    {
        if (($role !== 'owner' || $status !== 'active') && $user->portfoliosOwned()->exists()) {
            throw ValidationException::withMessages([
                $role !== 'owner' ? 'role' : 'status' => trans('app.errors.user_owns_portfolio'),
            ]);
        }
    }

    private function ensureTenantContinuity(User $user, string $role, string $status): void
    {
        if (
            $user->hasRole('tenant')
            && ($role !== 'tenant' || $status !== 'active')
            && $this->hasActiveTenantLease($user)
        ) {
            throw ValidationException::withMessages([
                $role !== 'tenant' ? 'role' : 'status' => trans('app.errors.user_has_active_lease'),
            ]);
        }
    }

    private function hasActiveTenantLease(User $user): bool
    {
        return $user->tenantProfile?->leases()->where('status', 'active')->exists() ?? false;
    }

    private function syncTenantProfile(User $user, string $role, string $status): void
    {
        if ($role !== 'tenant') {
            $user->tenantProfile?->update(['status' => 'inactive']);

            return;
        }

        if ($user->portfolio_id === null) {
            throw ValidationException::withMessages([
                'role' => trans('app.errors.tenant_requires_portfolio'),
            ]);
        }

        TenantProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'portfolio_id' => $user->portfolio_id,
                'profile_type' => 'individual',
                'status' => UserOptions::tenantProfileStatus($status),
            ],
        );
    }
}
