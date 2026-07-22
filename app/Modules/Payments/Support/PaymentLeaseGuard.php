<?php

namespace App\Modules\Payments\Support;

use App\Models\Lease;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Validation\ValidationException;

final class PaymentLeaseGuard
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function payable(User $actor, int $leaseId): Lease
    {
        $lease = Lease::query()->lockForUpdate()->findOrFail($leaseId);
        $this->portfolios->ensureAccess($actor, $lease->portfolio_id);
        $portfolio = $lease->portfolio()->lockForUpdate()->first();
        $tenantExists = $lease->tenant_profile_id
            && TenantProfile::query()
                ->whereKey($lease->tenant_profile_id)
                ->where('portfolio_id', $lease->portfolio_id)
                ->exists();

        if (! $portfolio || $portfolio->status !== 'active') {
            throw ValidationException::withMessages([
                'lease_id' => trans('app.errors.payment_portfolio_inactive'),
            ]);
        }

        if (! in_array($lease->status, PaymentOptions::PAYABLE_LEASE_STATUSES, true) || ! $tenantExists) {
            throw ValidationException::withMessages([
                'lease_id' => trans('app.errors.payment_lease_not_payable'),
            ]);
        }

        return $lease;
    }
}
