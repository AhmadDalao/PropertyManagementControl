<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\Assets\Support\AssetMetadata;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Database\Eloquent\Collection;

class AssetDetailPresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
        private readonly AssetMetadata $metadata,
        private readonly AssetHierarchy $hierarchy,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Asset $asset, User $actor): array
    {
        $asset->loadMissing([
            'portfolio',
            'parent',
            'children',
            'stakeholders.user',
            'maintenanceRequests.tenantProfile.user',
            'maintenanceRequests.assignedTo',
            'expenses',
            'documents',
        ]);
        $assetLeases = Lease::query()
            ->with(['tenantProfile.user', 'installments'])
            ->where('portfolio_id', $asset->portfolio_id)
            ->where('leaseable_id', $asset->id)
            ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
            ->orderByDesc('started_at')
            ->get();
        $activeLease = $assetLeases->firstWhere('status', 'active');
        $postedExpensesTotal = (float) $asset->expenses->where('status', 'posted')->sum('amount');
        $openMaintenanceCount = $asset->maintenanceRequests->whereIn('status', ['open', 'in_progress'])->count();
        $mapReady = $this->metadata->hasPosition($asset) && $this->metadata->hasIdentity($asset);
        $localizedZone = $this->resources->localized(
            $this->metadata->get($asset, 'zone_en') ?: $this->metadata->get($asset, 'zone'),
            $this->metadata->get($asset, 'zone_ar'),
        );
        $localizedAssetTitle = $this->resources->localized($asset->title_en, $asset->title_ar);
        $localizedPortfolioName = $this->resources->localized($asset->portfolio?->name_en, $asset->portfolio?->name_ar);
        $localizedParentTitle = $this->resources->localized($asset->parent?->title_en, $asset->parent?->title_ar);
        $localizedAddress = $this->resources->localized($asset->address, $asset->address_ar);
        $mapIdentity = trim(($localizedZone ?: 'No zone').' · '.($this->metadata->get($asset, 'land_number') ?: 'No land number'));
        $mapPosition = $this->metadata->coordinateLabel($asset) ?: $this->metadata->canvasPositionLabel($asset);
        $propertyMapHref = route(
            'property-map.index',
            $actor->hasRole('superadmin') ? ['portfolio_id' => $asset->portfolio_id] : []
        );

        return [
            'header' => [
                'eyebrow' => 'Asset detail',
                'title' => $localizedAssetTitle,
                'description' => trim($asset->code.' · '.$asset->asset_type.' · '.$asset->usage_type),
                'backHref' => route('assets.index'),
                'backLabel' => 'All assets',
                'actions' => [
                    ['label' => 'Edit asset', 'href' => route('assets.edit', $asset), 'variant' => 'primary'],
                    ['label' => 'Create child', 'href' => route('assets.create', ['parent_id' => $asset->id]), 'variant' => 'secondary'],
                    ['label' => 'Create lease', 'href' => route('leases.create', ['asset_id' => $asset->id]), 'variant' => 'secondary'],
                ],
            ],
            'spotlight' => [
                'eyebrow' => 'Clicked land record',
                'title' => $this->metadata->get($asset, 'land_number') ?: $asset->code,
                'subtitle' => $localizedZone ?: 'No zone recorded',
                'description' => $localizedAddress ?: 'No address recorded yet. Add the address and coordinates from Edit asset.',
                'status' => str($asset->occupancy_status)->headline()->toString(),
                'items' => $this->resources->detailItems([
                    ['label' => 'Property', 'value' => $localizedAssetTitle],
                    ['label' => 'Portfolio', 'value' => $localizedPortfolioName],
                    ['label' => 'Owner', 'value' => data_get($asset->stakeholders->firstWhere('relationship_type', 'owner'), 'user.name', 'Not assigned')],
                    ['label' => 'Manager', 'value' => data_get($asset->stakeholders->firstWhere('relationship_type', 'manager'), 'user.name', 'Not assigned')],
                    ['label' => 'Coordinates', 'value' => $this->metadata->coordinateLabel($asset)],
                    ['label' => 'Map position', 'value' => $this->metadata->canvasPositionLabel($asset)],
                ]),
                'actions' => [
                    ['label' => 'Back to map', 'href' => $propertyMapHref, 'variant' => 'light'],
                    ['label' => 'Edit map data', 'href' => route('assets.edit', $asset), 'variant' => 'primary'],
                ],
            ],
            'decisionCards' => [
                [
                    'title' => 'Map readiness',
                    'value' => $mapReady ? 'Ready' : 'Needs setup',
                    'detail' => $mapReady
                        ? $mapIdentity.' · '.$mapPosition
                        : 'Add zone, land number, and a map position before relying on this record in the owner map.',
                    'href' => $mapReady ? $propertyMapHref : route('assets.edit', $asset),
                    'actionLabel' => $mapReady ? 'Open map' : 'Fix map data',
                    'tone' => $mapReady ? 'teal' : 'danger',
                    'icon' => 'bi-map',
                ],
                [
                    'title' => 'Rental state',
                    'value' => $activeLease
                        ? str($activeLease->status)->headline()->toString()
                        : str($asset->occupancy_status)->headline()->toString(),
                    'detail' => $activeLease
                        ? trans('app.assets.tenant_balance', [
                            'tenant' => data_get($activeLease, 'tenantProfile.user.name', trans('app.map.not_assigned')),
                            'balance' => number_format((float) $activeLease->balance_remaining, 2).' '.$activeLease->currency,
                        ])
                        : 'No active lease is attached to this property record.',
                    'href' => $activeLease
                        ? route('leases.show', $activeLease)
                        : route('leases.create', ['asset_id' => $asset->id]),
                    'actionLabel' => $activeLease ? 'Open lease' : 'Create lease',
                    'tone' => $activeLease ? 'teal' : 'muted',
                    'icon' => 'bi-file-earmark-text',
                ],
                [
                    'title' => 'Operations risk',
                    'value' => $openMaintenanceCount,
                    'detail' => $openMaintenanceCount > 0
                        ? 'Open or in-progress maintenance needs owner or manager follow-up.'
                        : 'No open maintenance pressure on this property.',
                    'href' => route('maintenance-requests.create', ['asset_id' => $asset->id]),
                    'actionLabel' => $openMaintenanceCount > 0 ? 'Create follow-up' : 'Log request',
                    'tone' => $openMaintenanceCount > 0 ? 'danger' : 'teal',
                    'icon' => 'bi-tools',
                ],
                [
                    'title' => 'Financial position',
                    'value' => number_format((float) $asset->valuation_amount, 2).' '.$asset->currency,
                    'detail' => trans('app.assets.posted_expenses', [
                        'amount' => number_format($postedExpensesTotal, 2).' '.$asset->currency,
                    ]),
                    'href' => route('expenses.create', ['asset_id' => $asset->id]),
                    'actionLabel' => 'Add expense',
                    'tone' => $postedExpensesTotal > 0 ? 'primary' : 'muted',
                    'icon' => 'bi-cash-stack',
                ],
            ],
            'stats' => $this->resources->detailItems([
                ['label' => 'Valuation', 'value' => number_format((float) $asset->valuation_amount, 2).' '.$asset->currency, 'tone' => 'primary'],
                ['label' => 'Occupancy', 'value' => $asset->occupancy_status, 'tone' => $asset->occupancy_status === 'occupied' ? 'teal' : 'muted'],
                ['label' => 'Children', 'value' => $asset->children->count()],
                ['label' => 'Lease records', 'value' => $assetLeases->count(), 'tone' => $activeLease ? 'teal' : 'muted'],
                ['label' => 'Open maintenance', 'value' => $openMaintenanceCount, 'tone' => 'danger'],
                ['label' => 'Posted expenses', 'value' => number_format($postedExpensesTotal, 2).' '.$asset->currency, 'tone' => $postedExpensesTotal > 0 ? 'primary' : 'muted'],
            ]),
            'sections' => [
                [
                    'title' => 'Asset profile',
                    'description' => 'Core identity, hierarchy, and usage classification.',
                    'items' => $this->resources->detailItems([
                        ['label' => 'Arabic title', 'value' => $asset->title_ar],
                        ['label' => 'Code', 'value' => $asset->code],
                        ['label' => 'Portfolio', 'value' => $localizedPortfolioName, 'href' => $asset->portfolio ? route('portfolios.show', $asset->portfolio) : null],
                        ['label' => 'Parent', 'value' => $localizedParentTitle, 'href' => $asset->parent ? route('assets.show', $asset->parent) : null],
                        ['label' => 'Rentable', 'value' => $asset->rentable ? 'Yes' : 'No'],
                        ['label' => 'Status', 'value' => $asset->status],
                        ['label' => 'Area', 'value' => $asset->area ? trans('app.assets.area_sqm', ['area' => $asset->area]) : null],
                        ['label' => 'Address', 'value' => $localizedAddress],
                    ]),
                ],
                [
                    'title' => 'Map and land record',
                    'description' => 'Zone, land number, and map position used by the owner dashboard property map.',
                    'items' => $this->resources->detailItems([
                        ['label' => 'Zone', 'value' => $localizedZone],
                        ['label' => 'Land number', 'value' => $this->metadata->get($asset, 'land_number')],
                        ['label' => 'Latitude', 'value' => $this->metadata->get($asset, 'latitude')],
                        ['label' => 'Longitude', 'value' => $this->metadata->get($asset, 'longitude')],
                        ['label' => 'Map position', 'value' => $this->metadata->canvasPositionLabel($asset)],
                    ]),
                ],
                [
                    'title' => 'Ownership and management',
                    'description' => 'People responsible for this property node.',
                    'items' => $asset->stakeholders->map(fn ($stakeholder) => [
                        'label' => str($stakeholder->relationship_type)->headline()->toString(),
                        'value' => $stakeholder->user?->name,
                        'href' => $stakeholder->user ? route('users.show', $stakeholder->user) : null,
                        'tone' => $stakeholder->is_primary ? 'primary' : 'muted',
                    ])->values()->all(),
                ],
                [
                    'title' => 'Active rental',
                    'description' => 'Current lease connected to this asset.',
                    'items' => $this->resources->detailItems([
                        ['label' => 'Lease', 'value' => $activeLease?->code, 'href' => $activeLease ? route('leases.show', $activeLease) : null],
                        ['label' => 'Tenant', 'value' => $activeLease?->tenantProfile?->user?->name, 'href' => $activeLease?->tenantProfile ? route('tenants.show', $activeLease->tenantProfile) : null],
                        ['label' => 'Balance', 'value' => $activeLease ? number_format((float) $activeLease->balance_remaining, 2).' '.$activeLease->currency : null],
                    ]),
                ],
            ],
            'related' => $this->relatedRecords($asset, $assetLeases),
            'documents' => $this->resources->documentStrip($asset->documents),
            'timeline' => $this->resources->activityTimeline($asset),
        ];
    }

    /**
     * @param  Collection<int, Lease>  $assetLeases
     * @return array<int, array<string, mixed>>
     */
    private function relatedRecords(Asset $asset, Collection $assetLeases): array
    {
        return [
            [
                'title' => 'Child assets',
                'description' => 'Floors, units, spaces, and other nested records.',
                'columns' => ['Asset', 'Type', 'Occupancy', 'Open'],
                'rows' => $asset->children->map(fn (Asset $child) => [
                    'Asset' => $this->resources->localized($child->title_en, $child->title_ar),
                    'Type' => $child->asset_type,
                    'Occupancy' => $child->occupancy_status,
                    'Open' => ['label' => 'Open', 'href' => route('assets.show', $child)],
                ])->all(),
                'emptyText' => 'No child assets yet.',
                'actionHref' => route('assets.create', ['parent_id' => $asset->id]),
                'actionLabel' => 'Add child',
            ],
            [
                'title' => 'Leases',
                'description' => 'Active and historical contracts attached to this land or unit.',
                'columns' => ['Lease', 'Tenant', 'Status', 'Balance', 'Open'],
                'rows' => $assetLeases->map(fn (Lease $lease) => [
                    'Lease' => $lease->code,
                    'Tenant' => data_get($lease, 'tenantProfile.user.name', '-'),
                    'Status' => $lease->status,
                    'Balance' => number_format((float) $lease->balance_remaining, 2).' '.$lease->currency,
                    'Open' => ['label' => 'Open', 'href' => route('leases.show', $lease)],
                ])->all(),
                'emptyText' => 'No leases attached to this asset yet.',
                'actionHref' => route('leases.create', ['asset_id' => $asset->id]),
                'actionLabel' => 'Create lease',
            ],
            [
                'title' => 'Maintenance',
                'description' => 'Requests submitted for this asset.',
                'columns' => ['Request', 'Tenant', 'Status', 'Priority', 'Open'],
                'rows' => $asset->maintenanceRequests->take(8)->map(fn ($maintenanceRequest) => [
                    'Request' => '#'.$maintenanceRequest->id.' '.$maintenanceRequest->title,
                    'Tenant' => data_get($maintenanceRequest, 'tenantProfile.user.name', '-'),
                    'Status' => $maintenanceRequest->status,
                    'Priority' => $maintenanceRequest->priority,
                    'Open' => ['label' => 'Open', 'href' => route('maintenance-requests.show', $maintenanceRequest)],
                ])->all(),
                'emptyText' => 'No maintenance requests for this asset.',
                'actionHref' => route('maintenance-requests.create', ['asset_id' => $asset->id]),
                'actionLabel' => 'Create request',
            ],
            [
                'title' => 'Expenses',
                'description' => 'Costs attached to this property for net revenue reporting.',
                'columns' => ['Expense', 'Category', 'Status', 'Amount', 'Open'],
                'rows' => $asset->expenses->sortByDesc('incurred_on')->take(8)->map(fn ($expense) => [
                    'Expense' => $expense->title,
                    'Category' => $expense->category,
                    'Status' => $expense->status,
                    'Amount' => number_format((float) $expense->amount, 2).' '.$expense->currency,
                    'Open' => ['label' => 'Open', 'href' => route('expenses.show', $expense)],
                ])->values()->all(),
                'emptyText' => 'No expenses recorded for this asset.',
                'actionHref' => route('expenses.create', ['asset_id' => $asset->id]),
                'actionLabel' => 'Add expense',
            ],
        ];
    }
}
