<?php

namespace App\Modules\Users\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

final class UserContinuityGuard
{
    public function ensureUpdateAllowed(User $user, string $role, string $status): void
    {
        if (($role !== 'owner' || $status !== 'active') && $user->portfoliosOwned()->exists()) {
            throw ValidationException::withMessages([
                $role !== 'owner' ? 'role' : 'status' => trans('app.errors.user_owns_portfolio'),
            ]);
        }

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

    public function suspensionBlock(User $user): ?string
    {
        if ($user->portfoliosOwned()->exists()) {
            return $this->message('app.errors.user_owns_portfolio');
        }

        return $this->hasActiveTenantLease($user)
            ? $this->message('app.errors.user_has_active_lease')
            : null;
    }

    private function hasActiveTenantLease(User $user): bool
    {
        return $user->tenantProfile?->leases()->where('status', 'active')->exists() ?? false;
    }

    private function message(string $key): string
    {
        $message = trans($key);

        if (! is_string($message)) {
            throw new \LogicException("Translation {$key} must resolve to a string.");
        }

        return $message;
    }
}
