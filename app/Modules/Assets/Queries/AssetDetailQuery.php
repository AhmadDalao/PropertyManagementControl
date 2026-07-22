<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Assets\Data\AssetDetailData;
use App\Modules\Assets\Support\AssetAccess;
use App\Modules\Assets\Support\AssetHierarchy;
use Illuminate\Database\Eloquent\Builder;

class AssetDetailQuery
{
    private const RELATED_LIMIT = 8;

    public function __construct(
        private readonly AssetAccess $access,
        private readonly AssetHierarchy $hierarchy,
    ) {}

    public function get(Asset $asset, User $actor): AssetDetailData
    {
        $this->access->ensureCanManage($actor, $asset);
        $asset->loadMissing([
            'portfolio',
            'parent',
            'currentStakeholders.user',
        ]);
        $leaseQuery = $this->leaseQuery($asset);
        $maintenanceQuery = $asset->maintenanceRequests();
        $expenseQuery = $asset->expenses();

        return new AssetDetailData(
            asset: $asset,
            actor: $actor,
            children: $asset->children()
                ->limit(self::RELATED_LIMIT)
                ->get(['id', 'parent_id', 'title_en', 'title_ar', 'asset_type', 'occupancy_status']),
            leases: (clone $leaseQuery)
                ->orderByDesc('started_at')
                ->limit(self::RELATED_LIMIT)
                ->get(),
            maintenance: (clone $maintenanceQuery)
                ->with(['tenantProfile.user:id,name', 'assignedTo:id,name'])
                ->latest('requested_at')
                ->limit(self::RELATED_LIMIT)
                ->get(),
            expenses: (clone $expenseQuery)
                ->latest('incurred_on')
                ->limit(self::RELATED_LIMIT)
                ->get(),
            documents: $asset->documents()
                ->latest()
                ->limit(self::RELATED_LIMIT)
                ->get(),
            activeLease: (clone $leaseQuery)->where('status', 'active')->first(),
            childrenCount: $asset->children()->count(),
            leaseCount: (clone $leaseQuery)->count(),
            openMaintenanceCount: (clone $maintenanceQuery)
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
            postedExpenseTotal: (float) (clone $expenseQuery)
                ->where('status', 'posted')
                ->sum('amount'),
        );
    }

    /** @return Builder<Lease> */
    private function leaseQuery(Asset $asset): Builder
    {
        return Lease::query()
            ->select([
                'id',
                'portfolio_id',
                'tenant_profile_id',
                'leaseable_type',
                'leaseable_id',
                'code',
                'status',
                'currency',
                'started_at',
            ])
            ->with(['tenantProfile:id,user_id', 'tenantProfile.user:id,name'])
            ->withSum('installments as installments_due_total', 'amount_due')
            ->withSum('installments as installments_paid_total', 'amount_paid')
            ->where('portfolio_id', $asset->portfolio_id)
            ->where('leaseable_id', $asset->id)
            ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes());
    }
}
