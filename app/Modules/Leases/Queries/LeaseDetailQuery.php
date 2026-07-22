<?php

namespace App\Modules\Leases\Queries;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Leases\Data\LeaseDetailData;
use App\Modules\Leases\Support\LeaseAccess;

final class LeaseDetailQuery
{
    private const PAYMENT_LIMIT = 12;

    public function __construct(private readonly LeaseAccess $access) {}

    public function get(Lease $target, User $actor): LeaseDetailData
    {
        $this->access->ensureCanAccess($actor, $target);
        $lease = Lease::query()
            ->with([
                'portfolio',
                'tenantProfile.user',
                'leaseable',
                'managedBy',
                'previousLease',
                'renewalLease',
                'installments',
                'documents',
            ])
            ->whereKey($target->id)
            ->firstOrFail();
        $lease->setRelation('payments', $lease->payments()
            ->latest('received_on')
            ->latest('id')
            ->limit(self::PAYMENT_LIMIT)
            ->get());
        $this->access->ensureCanAccess($actor, $lease);

        return new LeaseDetailData(
            lease: $lease,
            actor: $actor,
            adminMode: $this->access->canManage($actor, $lease),
        );
    }
}
