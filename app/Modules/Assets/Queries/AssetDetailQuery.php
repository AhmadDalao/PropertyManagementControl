<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Data\AssetDetailData;
use App\Modules\Assets\Support\AssetAccess;

class AssetDetailQuery
{
    private const RELATED_LIMIT = 8;

    public function __construct(
        private readonly AssetAccess $access,
        private readonly AssetOperationsQuery $operations,
    ) {}

    public function get(Asset $asset, User $actor): AssetDetailData
    {
        $this->access->ensureCanManage($actor, $asset);
        $asset->loadMissing([
            'portfolio',
            'parent',
            'currentStakeholders.user',
        ]);

        return new AssetDetailData(
            asset: $asset,
            actor: $actor,
            children: $asset->children()
                ->limit(self::RELATED_LIMIT)
                ->get(['id', 'parent_id', 'title_en', 'title_ar', 'asset_type', 'occupancy_status']),
            operations: $this->operations->get($asset),
        );
    }
}
