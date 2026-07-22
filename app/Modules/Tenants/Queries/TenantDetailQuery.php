<?php

namespace App\Modules\Tenants\Queries;

use App\Models\Lease;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Tenants\Data\TenantDetailData;
use App\Modules\Tenants\Support\TenantAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TenantDetailQuery
{
    private const RELATED_LIMIT = 8;

    public function __construct(private readonly TenantAccess $access) {}

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
        $activeLease = $this->leases($tenant)
            ->with('documents')
            ->where('status', 'active')
            ->latest('started_at')
            ->first();
        $payableLease = $activeLease ?? $this->leases($tenant)
            ->whereIn('status', PaymentOptions::PAYABLE_LEASE_STATUSES)
            ->latest('started_at')
            ->first();

        return new TenantDetailData(
            tenant: $tenant,
            activeLease: $activeLease,
            payableLease: $payableLease,
            leases: $this->leases($tenant)->latest('started_at')->limit(self::RELATED_LIMIT)->get(),
            payments: $tenant->payments()->with('lease')->latest('received_on')->limit(self::RELATED_LIMIT)->get(),
            maintenance: $tenant->maintenanceRequests()
                ->with('asset')
                ->latest('requested_at')
                ->limit(self::RELATED_LIMIT)
                ->get(),
            lastPayment: $activeLease?->payments()
                ->where('status', 'posted')
                ->latest('received_on')
                ->first(),
            activeLeaseCount: (int) ($tenant->getAttribute('active_leases_count') ?? 0),
            openMaintenanceCount: (int) ($tenant->getAttribute('open_maintenance_count') ?? 0),
        );
    }

    /** @return HasMany<Lease, TenantProfile> */
    private function leases(TenantProfile $tenant): HasMany
    {
        return $tenant->leases()->with(['leaseable', 'installments']);
    }
}
