<?php

namespace App\Modules\Assets\Queries;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\Shared\MorphTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class AssetOperationsDocumentsQuery
{
    public function __construct(
        private AssetHierarchy $hierarchy,
        private MorphTypes $morphTypes,
    ) {}

    /**
     * @param  array<int, int>  $assetIds
     * @param  array<int, int>  $leaseIds
     * @return Collection<int, Document>
     */
    public function get(Asset $asset, array $assetIds, array $leaseIds): Collection
    {
        return Document::query()
            ->where('portfolio_id', $asset->portfolio_id)
            ->where(function (Builder $documents) use ($assetIds, $leaseIds): void {
                $documents
                    ->where(fn (Builder $assets) => $assets
                        ->whereIn('documentable_type', $this->hierarchy->leaseableTypes())
                        ->whereIn('documentable_id', $assetIds))
                    ->orWhere(fn (Builder $leases) => $leases
                        ->whereIn('documentable_type', $this->morphTypes->for(new Lease))
                        ->whereIn('documentable_id', $leaseIds));
            })
            ->latest()
            ->limit(8)
            ->get();
    }
}
