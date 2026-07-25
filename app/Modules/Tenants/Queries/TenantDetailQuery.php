<?php

namespace App\Modules\Tenants\Queries;

use App\Models\Lease;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Tenants\Data\TenantDetailData;
use App\Modules\Tenants\Support\TenantAccess;
use Illuminate\Database\Eloquent\Builder;

final class TenantDetailQuery
{
    private const RELATED_LIMIT = 8;

    public function __construct(
        private readonly TenantAccess $access,
        private readonly AssignedPropertyScope $assignments,
    ) {}

    public function get(TenantProfile $target, User $actor): TenantDetailData
    {
        $this->access->ensureCanManage($actor, $target);
        $tenant = TenantProfile::query()
            ->with(['portfolio', 'user'])
            ->withCount([
                'leases as active_leases_count' => fn (Builder $leases) => $leases->where('status', 'active'),
                'maintenanceRequests as open_maintenance_count' => fn (Builder $requests) => $requests
                    ->whereIn('status', ['open', 'in_progress']),
            ])
            ->whereKey($target->id)
            ->firstOrFail();
        $this->access->ensureCanManage($actor, $tenant);
        $activeLease = $this->leases($tenant, $actor)
            ->with('documents')
            ->where('status', 'active')
            ->latest('started_at')
            ->first();
        $payableLease = $activeLease ?? $this->leases($tenant, $actor)
            ->whereIn('status', PaymentOptions::PAYABLE_LEASE_STATUSES)
            ->latest('started_at')
            ->first();

        return new TenantDetailData(
            tenant: $tenant,
            activeLease: $activeLease,
            payableLease: $payableLease,
            leases: $this->leases($tenant, $actor)->latest('started_at')->limit(self::RELATED_LIMIT)->get(),
            payments: $this->assignments
                ->payments($tenant->payments()->getQuery(), $actor)
                ->with('lease')
                ->latest('received_on')
                ->limit(self::RELATED_LIMIT)
                ->get(),
            maintenance: $this->assignments
                ->maintenance($tenant->maintenanceRequests()->getQuery(), $actor)
                ->with('asset')
                ->latest('requested_at')
                ->limit(self::RELATED_LIMIT)
                ->get(),
            lastPayment: $activeLease?->payments()
                ->where('status', 'posted')
                ->latest('received_on')
                ->first(),
            activeLeaseCount: $this->leases($tenant, $actor)->where('status', 'active')->count(),
            openMaintenanceCount: $this->assignments
                ->maintenance($tenant->maintenanceRequests()->getQuery(), $actor)
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
        );
    }

    /** @return Builder<Lease> */
    private function leases(TenantProfile $tenant, User $actor): Builder
    {
        return $this->assignments
            ->leases($tenant->leases()->getQuery(), $actor)
            ->with(['leaseable', 'installments']);
    }
}
