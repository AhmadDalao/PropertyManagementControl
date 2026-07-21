<?php

namespace App\Modules\Assets\Actions;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\Assets\Support\AssetMetadata;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageAssets
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly AssetMetadata $metadata,
        private readonly AssetHierarchy $hierarchy,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Asset
    {
        $this->ensureManager($actor);
        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;

        if (! $portfolioId) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('validation.required', ['attribute' => trans('app.fields.portfolio')]),
            ]);
        }

        $portfolioId = (int) $portfolioId;
        $this->portfolios->ensureAccess($actor, $portfolioId);
        $this->ensureReferencesBelongToPortfolio($data, $portfolioId);

        return DB::transaction(function () use ($data, $portfolioId): Asset {
            $asset = Asset::query()->create([
                ...$this->attributes($data),
                'portfolio_id' => $portfolioId,
                'code' => filled($data['code'] ?? null) ? $data['code'] : Str::upper(Str::random(8)),
                'slug' => Str::slug($data['title_en']).'-'.Str::lower(Str::random(4)),
                'meta_json' => $this->metadata->merge($data),
            ]);

            $this->syncStakeholders(
                $asset,
                $this->nullableId($data['primary_owner_user_id'] ?? null),
                $this->nullableId($data['primary_manager_user_id'] ?? null),
            );

            return $asset;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Asset $asset, array $data): Asset
    {
        $this->ensureManager($actor);
        $this->portfolios->ensureAccess($actor, $asset->portfolio_id);
        $this->ensureReferencesBelongToPortfolio($data, $asset->portfolio_id, $asset);

        return DB::transaction(function () use ($asset, $data): Asset {
            $asset->update([
                ...$this->attributes($data),
                'meta_json' => $this->metadata->merge($data, $asset),
            ]);

            $this->syncStakeholders(
                $asset,
                $this->nullableId($data['primary_owner_user_id'] ?? null),
                $this->nullableId($data['primary_manager_user_id'] ?? null),
            );

            return $asset->refresh();
        });
    }

    public function archive(User $actor, Asset $asset): bool
    {
        $this->ensureManager($actor);
        $this->portfolios->ensureAccess($actor, $asset->portfolio_id);
        $assetIds = $this->hierarchy->descendantIdsIncluding($asset);
        $hasActiveLease = Lease::query()
            ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
            ->whereIn('leaseable_id', $assetIds)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveLease) {
            return false;
        }

        DB::transaction(fn () => Asset::query()->whereIn('id', $assetIds)->update(['status' => 'archived']));

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
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
        ];
    }

    private function syncStakeholders(Asset $asset, ?int $ownerId, ?int $managerId): void
    {
        $asset->stakeholders()->delete();

        foreach (['owner' => $ownerId, 'manager' => $managerId] as $relationship => $userId) {
            if (! $userId) {
                continue;
            }

            $asset->stakeholders()->create([
                'portfolio_id' => $asset->portfolio_id,
                'user_id' => $userId,
                'relationship_type' => $relationship,
                'is_primary' => true,
                'starts_on' => now()->toDateString(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureReferencesBelongToPortfolio(
        array $data,
        int $portfolioId,
        ?Asset $currentAsset = null
    ): void {
        if (! empty($data['parent_id'])) {
            if ($currentAsset) {
                abort_if((int) $data['parent_id'] === $currentAsset->id, 422, trans('app.errors.asset_self_parent'));
                abort_if(
                    in_array((int) $data['parent_id'], $this->hierarchy->descendantIdsIncluding($currentAsset), true),
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

    private function nullableId(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    private function ensureManager(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']),
            403,
            trans('app.errors.section_access_denied')
        );
    }
}
