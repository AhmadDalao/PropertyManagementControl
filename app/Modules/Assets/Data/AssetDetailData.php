<?php

namespace App\Modules\Assets\Data;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class AssetDetailData
{
    /**
     * @param  Collection<int, Asset>  $children
     */
    public function __construct(
        public Asset $asset,
        public User $actor,
        public Collection $children,
        public AssetOperationsData $operations,
    ) {}
}
