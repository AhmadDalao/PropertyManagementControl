<?php

namespace App\Modules\Payments\Support;

use App\Models\Payment;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;

class PaymentAccess
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function ensureCanAccess(User $actor, Payment $payment): void
    {
        if ($actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])) {
            $this->portfolios->ensureAccess($actor, $payment->portfolio_id);

            return;
        }

        abort_unless(
            $actor->hasRole('tenant')
                && $payment->tenantProfile()->where('user_id', $actor->id)->exists(),
            403,
            trans('app.errors.receipt_access_denied')
        );
    }

    public function ensureCanManage(User $actor, Payment $payment): void
    {
        $this->ensureManager($actor);
        $this->portfolios->ensureAccess($actor, $payment->portfolio_id);
    }

    public function ensureManager(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']),
            403,
            trans('app.errors.section_access_denied')
        );
    }
}
