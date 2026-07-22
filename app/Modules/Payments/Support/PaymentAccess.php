<?php

namespace App\Modules\Payments\Support;

use App\Models\Payment;
use App\Models\User;

final class PaymentAccess
{
    public function canManageSection(User $actor): bool
    {
        return $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']);
    }

    public function canAccess(User $actor, Payment $payment): bool
    {
        if ($this->canManageSection($actor)) {
            return $actor->hasRole('superadmin') || $actor->portfolio_id === $payment->portfolio_id;
        }

        return $actor->hasRole('tenant')
            && $payment->tenantProfile()->where('user_id', $actor->id)->exists();
    }

    public function canManage(User $actor, Payment $payment): bool
    {
        return $this->canManageSection($actor)
            && ($actor->hasRole('superadmin') || $actor->portfolio_id === $payment->portfolio_id);
    }

    public function ensureCanAccess(User $actor, Payment $payment): void
    {
        abort_unless($this->canAccess($actor, $payment), 403, trans('app.errors.receipt_access_denied'));
    }

    public function ensureCanManage(User $actor, Payment $payment): void
    {
        abort_unless($this->canManage($actor, $payment), 403, trans('app.errors.section_access_denied'));
    }

    public function ensureManager(User $actor): void
    {
        abort_unless($this->canManageSection($actor), 403, trans('app.errors.section_access_denied'));
    }
}
