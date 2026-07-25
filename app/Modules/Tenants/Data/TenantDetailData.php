<?php

namespace App\Modules\Tenants\Data;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class TenantDetailData
{
    /**
     * @param  Collection<int, Lease>  $leases
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, MaintenanceRequest>  $maintenance
     */
    public function __construct(
        public User $actor,
        public TenantProfile $tenant,
        public ?Lease $activeLease,
        public ?Lease $payableLease,
        public Collection $leases,
        public Collection $payments,
        public Collection $maintenance,
        public ?Payment $lastPayment,
        public int $activeLeaseCount,
        public int $openMaintenanceCount,
    ) {}
}
