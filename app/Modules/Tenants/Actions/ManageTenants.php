<?php

namespace App\Modules\Tenants\Actions;

use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Tenants\Support\TenantAccess;
use App\Modules\Tenants\Support\TenantOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ManageTenants
{
    public function __construct(
        private readonly TenantAccess $access,
        private readonly PortfolioScope $portfolios,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): TenantProfile
    {
        $this->access->ensureManager($actor);
        $portfolioId = $this->resolvePortfolioId($actor, $data['portfolio_id'] ?? null);
        $this->ensurePortfolioAcceptsTenants($portfolioId);

        return DB::transaction(function () use ($data, $portfolioId): TenantProfile {
            $user = $this->createPortalUser($portfolioId, $data);

            $tenant = TenantProfile::query()->create([
                'portfolio_id' => $portfolioId,
                'user_id' => $user->id,
                ...$this->profileAttributes($data),
            ]);

            return $tenant->load(['user', 'portfolio']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, TenantProfile $tenant, array $data): TenantProfile
    {
        $this->access->ensureCanManage($actor, $tenant);

        return DB::transaction(function () use ($tenant, $data): TenantProfile {
            $lockedTenant = TenantProfile::query()->lockForUpdate()->whereKey($tenant->id)->firstOrFail();
            $this->ensureStatusCanChange($lockedTenant, (string) $data['status']);

            $user = $lockedTenant->user()->lockForUpdate()->first();

            if (! $user) {
                $this->ensureReplacementAccountData($data);

                $user = $this->createPortalUser($lockedTenant->portfolio_id, $data);
                $lockedTenant->update(['user_id' => $user->id]);
            } else {
                $userAttributes = [
                    'portfolio_id' => $lockedTenant->portfolio_id,
                    'name' => $data['name'],
                    'email' => $data['email'] ?? $user->email,
                    'phone' => $data['phone'] ?? null,
                    'preferred_locale' => $data['preferred_locale'],
                    'status' => TenantOptions::userStatus((string) $data['status']),
                ];

                if (filled($data['password'] ?? null)) {
                    $userAttributes['password'] = Hash::make((string) $data['password']);
                    $userAttributes['force_password_reset'] = true;
                }

                $user->update($userAttributes);
                $user->syncRoles(['tenant']);
            }

            $lockedTenant->update($this->profileAttributes($data));

            return $lockedTenant->refresh()->load(['user', 'portfolio']);
        });
    }

    public function archive(User $actor, TenantProfile $tenant): bool
    {
        $this->access->ensureCanManage($actor, $tenant);

        return DB::transaction(function () use ($tenant): bool {
            $lockedTenant = TenantProfile::query()->lockForUpdate()->whereKey($tenant->id)->firstOrFail();

            if ($lockedTenant->leases()->where('status', 'active')->exists()) {
                return false;
            }

            $lockedTenant->update(['status' => 'blocked']);
            $lockedTenant->user()->update(['status' => 'suspended']);

            return true;
        });
    }

    private function resolvePortfolioId(User $actor, mixed $requestedPortfolioId): int
    {
        $portfolioId = filter_var(
            $requestedPortfolioId ?? $actor->portfolio_id,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if (! $portfolioId) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.tenant_requires_portfolio'),
            ]);
        }

        $this->portfolios->ensureAccess($actor, (int) $portfolioId);

        return (int) $portfolioId;
    }

    private function ensurePortfolioAcceptsTenants(int $portfolioId): void
    {
        if (! Portfolio::query()->whereKey($portfolioId)->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.tenant_portfolio_inactive'),
            ]);
        }
    }

    private function ensureStatusCanChange(TenantProfile $tenant, string $status): void
    {
        if (
            $status === 'blocked'
            && $tenant->status !== 'blocked'
            && $tenant->leases()->where('status', 'active')->exists()
        ) {
            throw ValidationException::withMessages([
                'status' => trans('app.errors.tenant_has_active_lease'),
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function ensureReplacementAccountData(array $data): void
    {
        $errors = [];

        if (blank($data['email'] ?? null)) {
            $errors['email'] = trans('validation.required', [
                'attribute' => trans('app.tenants.login_email'),
            ]);
        }

        if (blank($data['password'] ?? null)) {
            $errors['password'] = trans('app.errors.tenant_account_password_required');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function profileAttributes(array $data): array
    {
        return [
            'profile_type' => $data['profile_type'],
            'national_id' => $data['national_id'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ];
    }

    /** @param array<string, mixed> $data */
    private function createPortalUser(int $portfolioId, array $data): User
    {
        $user = User::query()->create([
            'portfolio_id' => $portfolioId,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'preferred_locale' => $data['preferred_locale'],
            'status' => TenantOptions::userStatus((string) $data['status']),
            'force_password_reset' => true,
            'password' => Hash::make((string) $data['password']),
        ]);

        $user->syncRoles(['tenant']);

        return $user;
    }
}
