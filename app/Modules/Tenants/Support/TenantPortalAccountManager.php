<?php

namespace App\Modules\Tenants\Support;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\AccountContinuityGuard;
use App\Modules\Shared\AccountSessionRevoker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class TenantPortalAccountManager
{
    public function __construct(
        private readonly AccountContinuityGuard $continuity,
        private readonly AccountSessionRevoker $sessions,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(int $portfolioId, array $data): User
    {
        $user = User::query()->create([
            'portfolio_id' => $portfolioId,
            'name' => trim((string) $data['name']),
            'email' => $this->email($data['email'] ?? null),
            'phone' => $this->optional($data['phone'] ?? null),
            'preferred_locale' => $data['preferred_locale'],
            'status' => TenantOptions::userStatus((string) $data['status']),
            'force_password_reset' => true,
            'password' => Hash::make((string) $data['password']),
        ]);
        $user->syncRoles(['tenant']);

        return $user;
    }

    /** @param array<string, mixed> $data */
    public function synchronize(TenantProfile $tenant, array $data): User
    {
        $user = $tenant->user()->lockForUpdate()->first();

        if (! $user) {
            $this->ensureReplacementData($data);
            $user = $this->create($tenant->portfolio_id, $data);
            $tenant->update(['user_id' => $user->id]);

            return $user;
        }

        $previousEmail = $user->email;
        $email = $this->email($data['email'] ?? $user->email);
        $status = TenantOptions::userStatus((string) $data['status']);
        $emailChanged = $email !== mb_strtolower($user->email);
        $passwordChanged = filled($data['password'] ?? null);
        $statusChanged = $status !== $user->status;
        $this->continuity->ensureUpdateAllowed($user, 'tenant', $status);
        $attributes = [
            'portfolio_id' => $tenant->portfolio_id,
            'name' => trim((string) $data['name']),
            'email' => $email,
            'phone' => $this->optional($data['phone'] ?? null),
            'preferred_locale' => $data['preferred_locale'],
            'status' => $status,
        ];

        if ($emailChanged) {
            $attributes['email_verified_at'] = null;
        }

        if ($passwordChanged) {
            $attributes['password'] = Hash::make((string) $data['password']);
            $attributes['force_password_reset'] = true;
        }

        $user->forceFill($attributes)->save();
        $user->syncRoles(['tenant']);

        if ($emailChanged || $passwordChanged || $statusChanged) {
            $this->sessions->revoke($user, $previousEmail);
        }

        return $user;
    }

    public function suspend(TenantProfile $tenant): void
    {
        $user = $tenant->user()->lockForUpdate()->first();

        if (! $user) {
            return;
        }

        $user->update(['status' => 'suspended']);
        $this->sessions->revoke($user);
    }

    /** @param array<string, mixed> $data */
    private function ensureReplacementData(array $data): void
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

    private function email(mixed $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function optional(mixed $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : '';

        return $normalized !== '' ? $normalized : null;
    }
}
