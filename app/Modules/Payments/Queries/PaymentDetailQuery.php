<?php

namespace App\Modules\Payments\Queries;

use App\Models\Asset;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Data\PaymentDetailData;
use App\Modules\Payments\Support\PaymentAccess;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PaymentDetailQuery
{
    public function __construct(private readonly PaymentAccess $access) {}

    public function get(Payment $payment, User $actor): PaymentDetailData
    {
        $this->access->ensureCanAccess($actor, $payment);
        $payment->load([
            'portfolio',
            'lease.leaseable',
            'tenantProfile.user',
            'recordedBy',
            'allocations' => fn (HasMany $allocations) => $allocations
                ->with('leaseInstallment')
                ->orderBy('id')
                ->limit(50),
            'documents',
        ])->loadCount('allocations')->loadSum('allocations', 'amount');
        $asset = $payment->lease?->leaseable instanceof Asset
            ? $payment->lease->leaseable
            : null;

        return new PaymentDetailData(
            payment: $payment,
            actor: $actor,
            adminMode: $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']),
            asset: $asset,
        );
    }
}
