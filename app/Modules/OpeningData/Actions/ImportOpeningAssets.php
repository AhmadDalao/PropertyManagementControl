<?php

namespace App\Modules\OpeningData\Actions;

use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Assets\Actions\CreateAsset;
use App\Modules\OpeningData\Support\OpeningDataAssetOrder;

final class ImportOpeningAssets
{
    public function __construct(
        private readonly CreateAsset $create,
        private readonly OpeningDataAssetOrder $order,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, Asset>
     */
    public function handle(User $actor, Portfolio $portfolio, array $rows): array
    {
        $parentCodes = collect($rows)->pluck('parent_code')->filter()->unique()->all();
        $assets = Asset::query()
            ->where('portfolio_id', $portfolio->id)
            ->whereIn('code', $parentCodes)
            ->get()
            ->keyBy(fn (Asset $asset): string => mb_strtoupper($asset->code))
            ->all();

        foreach ($this->order->sort($rows) as $row) {
            $parentCode = (string) ($row['parent_code'] ?? '');
            $parent = $parentCode !== '' ? ($assets[$parentCode] ?? null) : null;
            $asset = $this->create->handle($actor, [
                'portfolio_id' => $portfolio->id,
                'parent_id' => $parent?->id,
                'asset_type' => $row['asset_type'],
                'usage_type' => $row['usage_type'],
                'title_en' => $row['title_en'],
                'title_ar' => $row['title_ar'],
                'code' => $row['code'],
                'status' => $row['status'],
                'occupancy_status' => $row['occupancy_status'],
                'rentable' => $row['rentable'],
                'valuation_amount' => $row['valuation_amount'],
                'currency' => $row['currency'],
                'area' => $row['area'],
                'level_label' => $row['level_label'] ?? null,
                'unit_label' => $row['unit_label'] ?? null,
                'address' => $row['address_en'] ?? null,
                'address_ar' => $row['address_ar'] ?? null,
                'description_en' => $row['description_en'] ?? null,
                'description_ar' => $row['description_ar'] ?? null,
                'latitude' => $row['latitude'] ?? null,
                'longitude' => $row['longitude'] ?? null,
                'map_zone_en' => $row['zone_en'] ?? null,
                'map_zone_ar' => $row['zone_ar'] ?? null,
                'primary_owner_user_id' => $parent === null
                    ? $portfolio->owner_user_id
                    : null,
                'primary_manager_user_id' => null,
            ]);
            $assets[(string) $row['code']] = $asset;
        }

        return $assets;
    }
}
