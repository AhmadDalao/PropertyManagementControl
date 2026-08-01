<?php

namespace App\Modules\Reports\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Assets\Support\AssetAccess;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\Reports\Support\ReportQueryScope;

final readonly class PropertyReportContextQuery
{
    public function __construct(
        private AssetAccess $access,
        private AssetHierarchy $hierarchy,
        private ReportQueryScope $scope,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $actor, Asset $property): array
    {
        $this->access->ensureCanManage($actor, $property);
        abort_unless(
            $property->parent_id === null
                && in_array($property->asset_type, ['property', 'building'], true),
            404,
        );

        $property->load([
            'portfolio:id,code,name_en,name_ar',
            'currentStakeholders.user:id,name',
        ]);
        $assetIds = $this->hierarchy->descendantIdsIncluding($property);
        $assets = $this->access
            ->directoryScope(Asset::query(), $actor)
            ->whereIn('id', $assetIds)
            ->get([
                'id',
                'asset_type',
                'rentable',
                'occupancy_status',
            ]);
        $owner = $property->currentStakeholders->firstWhere('relationship_type', 'owner');
        $manager = $property->currentStakeholders->firstWhere('relationship_type', 'manager');
        $occupied = $assets
            ->where('rentable', true)
            ->whereIn('occupancy_status', ['occupied', 'partially_occupied'])
            ->count();
        $vacant = $assets
            ->where('rentable', true)
            ->where('occupancy_status', 'vacant')
            ->count();
        $allowedIds = $assets->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        $activeTenants = $this->scope
            ->apply(Lease::query(), $actor, $property->portfolio_id)
            ->where('status', 'active')
            ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
            ->whereIn('leaseable_id', $allowedIds)
            ->whereNotNull('tenant_profile_id')
            ->distinct()
            ->count('tenant_profile_id');

        return [
            'id' => $property->id,
            'code' => $property->code,
            'title_en' => $property->title_en,
            'title_ar' => $property->title_ar,
            'address_en' => $property->address,
            'address_ar' => $property->address_ar,
            'status' => $property->status,
            'usage_type' => $property->usage_type,
            'valuation_amount' => (float) $property->valuation_amount,
            'currency' => $property->currency ?: 'SAR',
            'is_showcase' => (bool) $property->is_showcase,
            'portfolio' => [
                'id' => $property->portfolio_id,
                'code' => $property->portfolio?->code,
                'name_en' => $property->portfolio?->name_en,
                'name_ar' => $property->portfolio?->name_ar,
            ],
            'owner' => $owner?->user ? ['id' => $owner->user->id, 'name' => $owner->user->name] : null,
            'manager' => $manager?->user ? ['id' => $manager->user->id, 'name' => $manager->user->name] : null,
            'structure' => [
                'records' => $assets->count(),
                'floors' => $assets->where('asset_type', 'floor')->count(),
                'units' => $assets->whereIn('asset_type', ['unit', 'space'])->count(),
                'rentable' => $assets->where('rentable', true)->count(),
                'occupied' => $occupied,
                'vacant' => $vacant,
                'active_tenants' => $activeTenants,
            ],
            'links' => [
                'asset' => route('assets.show', $property, false),
                'explorer' => route('property-explorer.index', ['property_id' => $property->id], false),
                'action_center' => route('action-center.index', ['property_id' => $property->id], false),
                'payments' => route('payments.index', ['property_id' => $property->id], false),
                'expenses' => route('expenses.index', ['property_id' => $property->id], false),
                'leases' => route('leases.index', ['property_id' => $property->id], false),
                'maintenance' => route('maintenance-requests.index', ['property_id' => $property->id], false),
                'documents' => route('documents.index', false),
            ],
        ];
    }
}
