<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Assets\PropertyMapPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(Request $request, PropertyMapPresenter $propertyMap): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->tableFilters($request, [
            'status' => 'all',
            'asset_type' => 'all',
            'usage_type' => 'all',
            'occupancy_status' => 'all',
            'rentable' => 'all',
        ]);
        $baseQuery = $this->scopeByPortfolio(Asset::query(), $actor);
        $assets = (clone $baseQuery)
            ->with(['portfolio', 'parent', 'stakeholders.user'])
            ->withCount([
                'children',
                'leases as active_leases_count' => fn ($query) => $query->where('status', 'active'),
            ]);

        $this->applyExactFilter($assets, $filters, 'portfolio_id');
        $this->applyExactFilter($assets, $filters, 'status');
        $this->applyExactFilter($assets, $filters, 'asset_type');
        $this->applyExactFilter($assets, $filters, 'usage_type');
        $this->applyExactFilter($assets, $filters, 'occupancy_status');

        if (($filters['rentable'] ?? 'all') !== 'all') {
            $assets->where('rentable', $filters['rentable'] === 'yes');
        }

        $mapQuery = clone $baseQuery;
        $this->applyExactFilter($mapQuery, $filters, 'portfolio_id');

        $this->applySearch($assets, $filters['search'], [
            'title_en',
            'title_ar',
            'code',
            'level_label',
            'unit_label',
            'address',
            'address_ar',
            fn ($query, $search, $like) => $query
                ->orWhere('meta_json->map->zone', 'like', $like)
                ->orWhere('meta_json->map->zone_en', 'like', $like)
                ->orWhere('meta_json->map->zone_ar', 'like', $like)
                ->orWhere('meta_json->map->land_number', 'like', $like),
            fn ($query, $search, $like) => $query->orWhereHas(
                'parent',
                fn ($parentQuery) => $parentQuery
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like)
            ),
            fn ($query, $search, $like) => $query->orWhereHas(
                'stakeholders.user',
                fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)
            ),
        ]);

        $userOptions = $this->scopeByPortfolio(
            User::query()->whereDoesntHave('roles', fn ($query) => $query->where('name', 'tenant'))->orderBy('name'),
            $actor
        )->get(['id', 'name', 'portfolio_id']);

        return Inertia::render('admin/assets/index', [
            'assets' => $this->paginateTable($assets, $request, $filters, [
                'created_at',
                'title_en',
                'code',
                'asset_type',
                'usage_type',
                'status',
                'occupancy_status',
                'valuation_amount',
            ]),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['active', 'inactive', 'archived'], $filters),
            'insights' => $this->assetInsights($baseQuery, $filters),
            'propertyMap' => $propertyMap->forQuery($mapQuery),
            'portfolioOptions' => $this->portfolioOptions($actor),
            'parentOptions' => (clone $baseQuery)->orderBy('title_en')->get()->map(fn (Asset $asset) => [
                'id' => $asset->id,
                'name' => $this->localized($asset->title_en, $asset->title_ar),
                'code' => $asset->code,
                'asset_type' => $asset->asset_type,
                'portfolio_id' => $asset->portfolio_id,
            ])->all(),
            'userOptions' => $userOptions,
        ]);
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->assetFormPage($actor),
        ]);
    }

    public function show(Request $request, Asset $asset): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $asset->portfolio_id);

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
            ->whereIn('leaseable_type', $this->assetLeaseableTypes())
            ->orderByDesc('started_at')
            ->get();
        $activeLease = $assetLeases->firstWhere('status', 'active');
        $postedExpensesTotal = (float) $asset->expenses->where('status', 'posted')->sum('amount');
        $openMaintenanceCount = $asset->maintenanceRequests->whereIn('status', ['open', 'in_progress'])->count();
        $mapReady = $this->assetHasMapPosition($asset) && $this->assetHasMapIdentity($asset);
        $localizedZone = $this->localized(
            $this->assetMapMeta($asset, 'zone_en') ?: $this->assetMapMeta($asset, 'zone'),
            $this->assetMapMeta($asset, 'zone_ar'),
        );
        $localizedAssetTitle = $this->localized($asset->title_en, $asset->title_ar);
        $localizedPortfolioName = $this->localized($asset->portfolio?->name_en, $asset->portfolio?->name_ar);
        $localizedParentTitle = $this->localized($asset->parent?->title_en, $asset->parent?->title_ar);
        $localizedAddress = $this->localized($asset->address, $asset->address_ar);
        $mapIdentity = trim(($localizedZone ?: 'No zone').' · '.($this->assetMapMeta($asset, 'land_number') ?: 'No land number'));
        $mapPosition = $this->assetCoordinateLabel($asset) ?: $this->mapPositionLabel($asset);
        $propertyMapHref = route(
            'property-map.index',
            $actor->hasRole('superadmin') ? ['portfolio_id' => $asset->portfolio_id] : []
        );

        return Inertia::render('admin/resource-show', [
            'detailPage' => [
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
                    'title' => $this->assetMapMeta($asset, 'land_number') ?: $asset->code,
                    'subtitle' => $localizedZone ?: 'No zone recorded',
                    'description' => $localizedAddress ?: 'No address recorded yet. Add the address and coordinates from Edit asset.',
                    'status' => str($asset->occupancy_status)->headline()->toString(),
                    'items' => $this->detailItems([
                        ['label' => 'Property', 'value' => $localizedAssetTitle],
                        ['label' => 'Portfolio', 'value' => $localizedPortfolioName],
                        ['label' => 'Owner', 'value' => $asset->stakeholders->firstWhere('relationship_type', 'owner')?->user?->name ?? 'Not assigned'],
                        ['label' => 'Manager', 'value' => $asset->stakeholders->firstWhere('relationship_type', 'manager')?->user?->name ?? 'Not assigned'],
                        ['label' => 'Coordinates', 'value' => $this->assetCoordinateLabel($asset)],
                        ['label' => 'Map position', 'value' => $this->mapPositionLabel($asset)],
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
                        'value' => $activeLease ? str($activeLease->status)->headline()->toString() : str($asset->occupancy_status)->headline()->toString(),
                        'detail' => $activeLease
                            ? trans('app.assets.tenant_balance', [
                                'tenant' => $activeLease->tenantProfile?->user?->name ?? trans('app.map.not_assigned'),
                                'balance' => number_format((float) $activeLease->balance_remaining, 2).' '.$activeLease->currency,
                            ])
                            : 'No active lease is attached to this property record.',
                        'href' => $activeLease ? route('leases.show', $activeLease) : route('leases.create', ['asset_id' => $asset->id]),
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
                'stats' => $this->detailItems([
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
                        'items' => $this->detailItems([
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
                        'items' => $this->detailItems([
                            ['label' => 'Zone', 'value' => $localizedZone],
                            ['label' => 'Land number', 'value' => $this->assetMapMeta($asset, 'land_number')],
                            ['label' => 'Latitude', 'value' => $this->assetMapMeta($asset, 'latitude')],
                            ['label' => 'Longitude', 'value' => $this->assetMapMeta($asset, 'longitude')],
                            ['label' => 'Map position', 'value' => $this->mapPositionLabel($asset)],
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
                        'items' => $this->detailItems([
                            ['label' => 'Lease', 'value' => $activeLease?->code, 'href' => $activeLease ? route('leases.show', $activeLease) : null],
                            ['label' => 'Tenant', 'value' => $activeLease?->tenantProfile?->user?->name, 'href' => $activeLease?->tenantProfile ? route('tenants.show', $activeLease->tenantProfile) : null],
                            ['label' => 'Balance', 'value' => $activeLease ? number_format((float) $activeLease->balance_remaining, 2).' '.$activeLease->currency : null],
                        ]),
                    ],
                ],
                'related' => [
                    [
                        'title' => 'Child assets',
                        'description' => 'Floors, units, spaces, and other nested records.',
                        'columns' => ['Asset', 'Type', 'Occupancy', 'Open'],
                        'rows' => $asset->children->map(fn (Asset $child) => [
                            'Asset' => $this->localized($child->title_en, $child->title_ar),
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
                            'Tenant' => $lease->tenantProfile?->user?->name ?? '-',
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
                            'Tenant' => $maintenanceRequest->tenantProfile?->user?->name ?? '-',
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
                ],
                'documents' => $this->documentStrip($asset->documents),
                'timeline' => $this->activityTimeline($asset),
            ],
        ]);
    }

    public function edit(Request $request, Asset $asset): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $asset->portfolio_id);
        $asset->loadMissing('stakeholders.user');

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->assetFormPage($actor, $asset),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'parent_id' => ['nullable', 'integer', 'exists:assets,id'],
            'asset_type' => ['required', 'string'],
            'usage_type' => ['required', 'string'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:assets,code'],
            'status' => ['required', 'string'],
            'occupancy_status' => ['required', 'string'],
            'rentable' => ['nullable', 'boolean'],
            'valuation_amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'area' => ['nullable', 'numeric'],
            'level_label' => ['nullable', 'string', 'max:255'],
            'unit_label' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'address_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'map_zone' => ['nullable', 'string', 'max:80'],
            'map_zone_en' => ['nullable', 'string', 'max:80'],
            'map_zone_ar' => ['nullable', 'string', 'max:80'],
            'land_number' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_x' => ['nullable', 'numeric', 'between:0,100'],
            'map_y' => ['nullable', 'numeric', 'between:0,100'],
            'primary_owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'primary_manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);
        $this->ensureAssetReferencesBelongToPortfolio($data, $portfolioId);

        $asset = Asset::query()->create([
            'portfolio_id' => $portfolioId,
            'parent_id' => $data['parent_id'] ?? null,
            'asset_type' => $data['asset_type'],
            'usage_type' => $data['usage_type'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'code' => $data['code'] ?: Str::upper(Str::random(8)),
            'slug' => Str::slug($data['title_en']).'-'.Str::lower(Str::random(4)),
            'status' => $data['status'],
            'occupancy_status' => $data['occupancy_status'],
            'rentable' => (bool) ($data['rentable'] ?? false),
            'valuation_amount' => $data['valuation_amount'] ?? 0,
            'currency' => $data['currency'] ?? 'SAR',
            'area' => $data['area'] ?? null,
            'level_label' => $data['level_label'] ?? null,
            'unit_label' => $data['unit_label'] ?? null,
            'address' => $data['address'] ?? null,
            'address_ar' => $data['address_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'meta_json' => $this->assetMetaFromData($data),
        ]);

        $this->syncStakeholders($asset, $data['primary_owner_user_id'] ?? null, $data['primary_manager_user_id'] ?? null);

        return to_route('assets.show', $asset)->with('success', trans('app.messages.record_created', [
            'resource' => trans('app.nav.assets'),
            'name' => app()->isLocale('ar') ? $asset->title_ar : $asset->title_en,
        ]));
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $asset->portfolio_id);

        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:assets,id'],
            'asset_type' => ['required', 'string'],
            'usage_type' => ['required', 'string'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string'],
            'occupancy_status' => ['required', 'string'],
            'rentable' => ['nullable', 'boolean'],
            'valuation_amount' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string', 'size:3'],
            'area' => ['nullable', 'numeric'],
            'level_label' => ['nullable', 'string', 'max:255'],
            'unit_label' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'address_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'map_zone' => ['nullable', 'string', 'max:80'],
            'map_zone_en' => ['nullable', 'string', 'max:80'],
            'map_zone_ar' => ['nullable', 'string', 'max:80'],
            'land_number' => ['nullable', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_x' => ['nullable', 'numeric', 'between:0,100'],
            'map_y' => ['nullable', 'numeric', 'between:0,100'],
            'primary_owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'primary_manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $this->ensureAssetReferencesBelongToPortfolio($data, $asset->portfolio_id, $asset);

        $asset->update([
            'parent_id' => $data['parent_id'] ?? null,
            'asset_type' => $data['asset_type'],
            'usage_type' => $data['usage_type'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'status' => $data['status'],
            'occupancy_status' => $data['occupancy_status'],
            'rentable' => (bool) ($data['rentable'] ?? false),
            'valuation_amount' => $data['valuation_amount'] ?? 0,
            'currency' => $data['currency'] ?? 'SAR',
            'area' => $data['area'] ?? null,
            'level_label' => $data['level_label'] ?? null,
            'unit_label' => $data['unit_label'] ?? null,
            'address' => $data['address'] ?? null,
            'address_ar' => $data['address_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'meta_json' => $this->assetMetaFromData($data, $asset),
        ]);

        $this->syncStakeholders($asset, $data['primary_owner_user_id'] ?? null, $data['primary_manager_user_id'] ?? null);

        return to_route('assets.show', $asset)->with('success', trans('app.messages.record_updated', [
            'resource' => trans('app.nav.assets'),
            'name' => app()->isLocale('ar') ? $asset->title_ar : $asset->title_en,
        ]));
    }

    public function destroy(Request $request, Asset $asset): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $asset->portfolio_id);

        $assetIds = $this->assetAndDescendantIds($asset);

        if (Lease::query()
            ->whereIn('leaseable_type', $this->assetLeaseableTypes())
            ->whereIn('leaseable_id', $assetIds)
            ->where('status', 'active')
            ->exists()) {
            return back()->with('error', trans('app.errors.asset_has_active_lease'));
        }

        DB::transaction(function () use ($assetIds) {
            foreach ($assetIds as $assetId) {
                Asset::query()
                    ->whereKey($assetId)
                    ->update(['status' => 'archived']);
            }
        });

        return to_route('assets.index')->with('success', trans('app.messages.asset_archived', [
            'name' => app()->isLocale('ar') ? $asset->title_ar : $asset->title_en,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function assetInsights(Builder $baseQuery, array $filters): array
    {
        $query = clone $baseQuery;
        $this->applyExactFilter($query, $filters, 'portfolio_id');

        $totalAssets = (clone $query)->count();
        $rentableAssets = (clone $query)->where('rentable', true)->count();
        $vacantRentableAssets = (clone $query)
            ->where('rentable', true)
            ->where('occupancy_status', 'vacant')
            ->count();

        return [
            'total_assets' => $totalAssets,
            'total_value' => (float) (clone $query)->sum('valuation_amount'),
            'rentable_assets' => $rentableAssets,
            'vacant_rentable_assets' => $vacantRentableAssets,
            'occupied_assets' => (clone $query)->where('occupancy_status', 'occupied')->count(),
            'maintenance_assets' => (clone $query)->where('occupancy_status', 'maintenance')->count(),
            'buildings' => (clone $query)->where('asset_type', 'building')->count(),
            'floors' => (clone $query)->where('asset_type', 'floor')->count(),
            'units' => (clone $query)->where('asset_type', 'unit')->count(),
            'spaces' => (clone $query)->where('asset_type', 'space')->count(),
            'missing_owner' => (clone $query)->whereDoesntHave(
                'stakeholders',
                fn ($stakeholderQuery) => $stakeholderQuery->where('relationship_type', 'owner')->where('is_primary', true)
            )->count(),
            'missing_manager' => (clone $query)->whereDoesntHave(
                'stakeholders',
                fn ($stakeholderQuery) => $stakeholderQuery->where('relationship_type', 'manager')->where('is_primary', true)
            )->count(),
            'rentable_occupancy_rate' => $rentableAssets > 0
                ? round((($rentableAssets - $vacantRentableAssets) / $rentableAssets) * 100, 1)
                : 0,
        ];
    }

    private function syncStakeholders(Asset $asset, ?int $ownerId, ?int $managerId): void
    {
        $asset->stakeholders()->delete();

        if ($ownerId) {
            $asset->stakeholders()->create([
                'portfolio_id' => $asset->portfolio_id,
                'user_id' => $ownerId,
                'relationship_type' => 'owner',
                'is_primary' => true,
                'starts_on' => now()->toDateString(),
            ]);
        }

        if ($managerId) {
            $asset->stakeholders()->create([
                'portfolio_id' => $asset->portfolio_id,
                'user_id' => $managerId,
                'relationship_type' => 'manager',
                'is_primary' => true,
                'starts_on' => now()->toDateString(),
            ]);
        }
    }

    private function assetFormPage(User $actor, ?Asset $asset = null): array
    {
        $portfolioId = $asset?->portfolio_id ?? (int) request('portfolio_id', $actor->portfolio_id ?? $this->portfolioOptions($actor)[0]['id'] ?? 0);
        $owner = $asset?->stakeholders?->firstWhere('relationship_type', 'owner');
        $manager = $asset?->stakeholders?->firstWhere('relationship_type', 'manager');
        $parentOptions = $this->scopeByPortfolio(Asset::query()->orderBy('title_en'), $actor)
            ->when($asset, fn ($query) => $query->whereKeyNot($asset->id))
            ->get()
            ->map(fn (Asset $record) => [
                'value' => $record->id,
                'label' => $this->localized($record->title_en, $record->title_ar).' · '.$record->code.' · '.$record->asset_type,
            ])
            ->prepend(['value' => '', 'label' => 'No parent'])
            ->values()
            ->all();
        $userOptions = $this->scopeByPortfolio(
            User::query()->whereDoesntHave('roles', fn ($query) => $query->where('name', 'tenant'))->orderBy('name'),
            $actor
        )->get()->map(fn (User $user) => ['value' => $user->id, 'label' => $user->name])
            ->prepend(['value' => '', 'label' => 'Unassigned'])
            ->values()
            ->all();

        $fields = [];

        if ($actor->hasRole('superadmin') && $asset === null) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'required' => true,
                'options' => collect($this->portfolioOptions($actor))->map(fn ($portfolio) => ['value' => $portfolio['id'], 'label' => $portfolio['name']])->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'parent_id', 'label' => 'Parent asset', 'type' => 'select', 'options' => $parentOptions],
            ['name' => 'asset_type', 'label' => 'Asset type', 'type' => 'select', 'required' => true, 'options' => $this->valueOptions(['property', 'building', 'floor', 'unit', 'space'])],
            ['name' => 'usage_type', 'label' => 'Usage type', 'type' => 'select', 'required' => true, 'options' => $this->valueOptions(['residential', 'commercial', 'mixed', 'personal'])],
            ['name' => 'title_en', 'label' => 'English title', 'required' => true],
            ['name' => 'title_ar', 'label' => 'Arabic title', 'required' => true],
        ];

        if ($asset === null) {
            $fields[] = ['name' => 'code', 'label' => 'Code', 'help' => 'Leave blank to generate one.'];
        }

        $fields = [
            ...$fields,
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => $this->valueOptions(['active', 'inactive', 'archived'])],
            ['name' => 'occupancy_status', 'label' => 'Occupancy', 'type' => 'select', 'required' => true, 'options' => $this->valueOptions(['vacant', 'occupied', 'reserved', 'maintenance'])],
            ['name' => 'valuation_amount', 'label' => 'Valuation', 'type' => 'number', 'min' => 0],
            ['name' => 'currency', 'label' => 'Currency', 'placeholder' => 'SAR'],
            ['name' => 'area', 'label' => 'Area', 'type' => 'number', 'min' => 0],
            ['name' => 'level_label', 'label' => 'Floor / level label'],
            ['name' => 'unit_label', 'label' => 'Unit / space label'],
            ['name' => 'map_zone_en', 'label' => trans('app.fields.zone_en'), 'placeholder' => 'North Riyadh'],
            ['name' => 'map_zone_ar', 'label' => trans('app.fields.zone_ar'), 'placeholder' => 'شمال الرياض'],
            ['name' => 'land_number', 'label' => 'Land number', 'placeholder' => 'Land 42', 'help' => 'Click target label for the property map.'],
            ['name' => 'latitude', 'label' => 'Latitude', 'type' => 'number', 'step' => '0.000001', 'min' => -90, 'max' => 90],
            ['name' => 'longitude', 'label' => 'Longitude', 'type' => 'number', 'step' => '0.000001', 'min' => -180, 'max' => 180],
            ['name' => 'primary_owner_user_id', 'label' => 'Primary owner', 'type' => 'select', 'options' => $userOptions],
            ['name' => 'primary_manager_user_id', 'label' => 'Primary manager', 'type' => 'select', 'options' => $userOptions],
            ['name' => 'address', 'label' => trans('app.fields.address_en'), 'type' => 'textarea', 'rows' => 2],
            ['name' => 'address_ar', 'label' => trans('app.fields.address_ar'), 'type' => 'textarea', 'rows' => 2],
            ['name' => 'description_en', 'label' => 'English description', 'type' => 'textarea'],
            ['name' => 'description_ar', 'label' => 'Arabic description', 'type' => 'textarea'],
            ['name' => 'rentable', 'label' => 'Rentable', 'type' => 'checkbox', 'help' => 'Only rentable assets can be leased.'],
        ];

        return [
            'title' => $asset
                ? trans('app.actions.edit').' '.$this->localized($asset->title_en, $asset->title_ar)
                : 'Create asset',
            'description' => 'Build the property tree cleanly before leases, documents, and reports depend on it.',
            'backHref' => $asset ? route('assets.show', $asset) : route('assets.index'),
            'backLabel' => $asset ? 'Asset detail' : 'All assets',
            'action' => $asset ? route('assets.update', $asset) : route('assets.store'),
            'method' => $asset ? 'put' : 'post',
            'submitLabel' => $asset ? 'Update asset' : 'Create asset',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) $portfolioId,
                'parent_id' => (string) ($asset?->parent_id ?? request('parent_id', '')),
                'asset_type' => $asset?->asset_type ?? 'building',
                'usage_type' => $asset?->usage_type ?? 'residential',
                'title_en' => $asset?->title_en ?? '',
                'title_ar' => $asset?->title_ar ?? '',
                'code' => $asset?->code ?? '',
                'status' => $asset?->status ?? 'active',
                'occupancy_status' => $asset?->occupancy_status ?? 'vacant',
                'valuation_amount' => (float) ($asset?->valuation_amount ?? 0),
                'currency' => $asset?->currency ?? 'SAR',
                'area' => (float) ($asset?->area ?? 0),
                'level_label' => $asset?->level_label ?? '',
                'unit_label' => $asset?->unit_label ?? '',
                'map_zone_en' => $this->assetMapMeta($asset, 'zone_en') ?? $this->assetMapMeta($asset, 'zone') ?? '',
                'map_zone_ar' => $this->assetMapMeta($asset, 'zone_ar') ?? '',
                'land_number' => $this->assetMapMeta($asset, 'land_number') ?? '',
                'latitude' => $this->assetMapMeta($asset, 'latitude') ?? '',
                'longitude' => $this->assetMapMeta($asset, 'longitude') ?? '',
                'primary_owner_user_id' => (string) ($owner?->user_id ?? ''),
                'primary_manager_user_id' => (string) ($manager?->user_id ?? ''),
                'address' => $asset?->address ?? '',
                'address_ar' => $asset?->address_ar ?? '',
                'description_en' => $asset?->description_en ?? '',
                'description_ar' => $asset?->description_ar ?? '',
                'rentable' => (bool) ($asset?->rentable ?? false),
            ],
        ];
    }

    private function valueOptions(array $values): array
    {
        return collect($values)
            ->map(fn (string $value) => ['value' => $value, 'label' => str($value)->replace('_', ' ')->headline()->toString()])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function assetMetaFromData(array $data, ?Asset $asset = null): ?array
    {
        $meta = $asset?->meta_json ?? [];
        $map = is_array($meta['map'] ?? null) ? $meta['map'] : [];

        $values = [
            'zone_en' => $data['map_zone_en'] ?? $data['map_zone'] ?? null,
            'zone_ar' => $data['map_zone_ar'] ?? null,
            'zone' => $data['map_zone_en'] ?? $data['map_zone'] ?? null,
            'land_number' => $data['land_number'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'x' => $data['map_x'] ?? null,
            'y' => $data['map_y'] ?? null,
        ];

        foreach ($values as $key => $value) {
            if ($value === null || $value === '') {
                unset($map[$key]);

                continue;
            }

            $map[$key] = in_array($key, ['latitude', 'longitude', 'x', 'y'], true)
                ? (float) $value
                : trim((string) $value);
        }

        if ($map === []) {
            unset($meta['map']);
        } else {
            $meta['map'] = $map;
        }

        return $meta === [] ? null : $meta;
    }

    private function assetMapMeta(?Asset $asset, string $key): mixed
    {
        if (! $asset) {
            return null;
        }

        $meta = $asset->meta_json ?? [];
        $map = is_array($meta) ? ($meta['map'] ?? []) : [];

        return is_array($map) ? ($map[$key] ?? null) : null;
    }

    private function mapPositionLabel(Asset $asset): ?string
    {
        $x = $this->assetMapMeta($asset, 'x');
        $y = $this->assetMapMeta($asset, 'y');

        if ($x === null || $y === null) {
            return null;
        }

        return "{$x}, {$y}";
    }

    private function assetCoordinateLabel(Asset $asset): ?string
    {
        $latitude = $this->assetMapMeta($asset, 'latitude');
        $longitude = $this->assetMapMeta($asset, 'longitude');

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return "{$latitude}, {$longitude}";
    }

    private function assetHasMapIdentity(Asset $asset): bool
    {
        return filled($this->assetMapMeta($asset, 'zone'))
            && filled($this->assetMapMeta($asset, 'land_number'));
    }

    private function assetHasMapPosition(Asset $asset): bool
    {
        $hasCoordinates = filled($this->assetMapMeta($asset, 'latitude'))
            && filled($this->assetMapMeta($asset, 'longitude'));
        $hasCanvasPosition = filled($this->assetMapMeta($asset, 'x'))
            && filled($this->assetMapMeta($asset, 'y'));

        return $hasCoordinates || $hasCanvasPosition;
    }

    /**
     * @return array<int, string>
     */
    private function assetLeaseableTypes(): array
    {
        $asset = new Asset;

        return array_values(array_unique([Asset::class, $asset->getMorphClass()]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureAssetReferencesBelongToPortfolio(array $data, int $portfolioId, ?Asset $currentAsset = null): void
    {
        if (! empty($data['parent_id'])) {
            if ($currentAsset) {
                abort_if((int) $data['parent_id'] === $currentAsset->id, 422, trans('app.errors.asset_self_parent'));
                abort_if(
                    in_array((int) $data['parent_id'], $this->assetAndDescendantIds($currentAsset), true),
                    422,
                    trans('app.errors.asset_descendant_parent')
                );
            }

            abort_unless(
                Asset::query()->whereKey($data['parent_id'])->where('portfolio_id', $portfolioId)->exists(),
                422,
                trans('app.errors.parent_asset_portfolio_mismatch')
            );
        }

        foreach (['primary_owner_user_id', 'primary_manager_user_id'] as $userKey) {
            if (empty($data[$userKey])) {
                continue;
            }

            abort_unless(
                User::query()->whereKey($data[$userKey])->where('portfolio_id', $portfolioId)->exists(),
                422,
                trans('app.errors.assigned_user_portfolio_mismatch')
            );
        }
    }

    /**
     * @return array<int, int>
     */
    private function assetAndDescendantIds(Asset $asset): array
    {
        $ids = [$asset->id];
        $stack = [$asset->id];

        while ($stack !== []) {
            $children = Asset::query()
                ->whereIn('parent_id', $stack)
                ->pluck('id')
                ->all();

            $stack = array_values(array_diff($children, $ids));
            $ids = array_values(array_unique([...$ids, ...$children]));
        }

        return $ids;
    }
}
