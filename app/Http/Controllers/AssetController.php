<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(Request $request): Response
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

        $this->applySearch($assets, $filters['search'], [
            'title_en',
            'title_ar',
            'code',
            'level_label',
            'unit_label',
            'address',
            fn ($query, $search, $like) => $query->orWhereHas(
                'parent',
                fn ($parentQuery) => $parentQuery->where('title_en', 'like', $like)->orWhere('code', 'like', $like)
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
            'portfolioOptions' => $this->portfolioOptions($actor),
            'parentOptions' => (clone $baseQuery)->orderBy('title_en')->get()->map(fn (Asset $asset) => [
                'id' => $asset->id,
                'name' => $asset->title_en,
                'code' => $asset->code,
                'asset_type' => $asset->asset_type,
                'portfolio_id' => $asset->portfolio_id,
            ])->all(),
            'userOptions' => $userOptions,
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
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
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
            'description_en' => $data['description_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
        ]);

        $this->syncStakeholders($asset, $data['primary_owner_user_id'] ?? null, $data['primary_manager_user_id'] ?? null);

        return to_route('assets.index')->with('success', "Asset {$asset->title_en} created.");
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
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
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
            'description_en' => $data['description_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
        ]);

        $this->syncStakeholders($asset, $data['primary_owner_user_id'] ?? null, $data['primary_manager_user_id'] ?? null);

        return to_route('assets.index')->with('success', "Asset {$asset->title_en} updated.");
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
            return back()->with('error', 'Terminate active leases before archiving this asset.');
        }

        DB::transaction(function () use ($assetIds) {
            foreach ($assetIds as $assetId) {
                Asset::query()
                    ->whereKey($assetId)
                    ->update(['status' => 'archived']);
            }
        });

        return to_route('assets.index')->with('success', "Asset {$asset->title_en} archived.");
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

    /**
     * @return array<int, string>
     */
    private function assetLeaseableTypes(): array
    {
        $asset = new Asset();

        return array_values(array_unique([Asset::class, $asset->getMorphClass()]));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function ensureAssetReferencesBelongToPortfolio(array $data, int $portfolioId, ?Asset $currentAsset = null): void
    {
        if (! empty($data['parent_id'])) {
            if ($currentAsset) {
                abort_if((int) $data['parent_id'] === $currentAsset->id, 422, 'An asset cannot be its own parent.');
                abort_if(
                    in_array((int) $data['parent_id'], $this->assetAndDescendantIds($currentAsset), true),
                    422,
                    'An asset cannot be moved under one of its descendants.'
                );
            }

            abort_unless(
                Asset::query()->whereKey($data['parent_id'])->where('portfolio_id', $portfolioId)->exists(),
                422,
                'Parent asset does not belong to this portfolio.'
            );
        }

        foreach (['primary_owner_user_id', 'primary_manager_user_id'] as $userKey) {
            if (empty($data[$userKey])) {
                continue;
            }

            abort_unless(
                User::query()->whereKey($data[$userKey])->where('portfolio_id', $portfolioId)->exists(),
                422,
                'Assigned user does not belong to this portfolio.'
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
