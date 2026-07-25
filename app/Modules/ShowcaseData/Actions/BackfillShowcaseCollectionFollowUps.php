<?php

namespace App\Modules\ShowcaseData\Actions;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\ShowcaseDataset;
use App\Models\User;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\ShowcaseData\Generators\ShowcaseCollectionFollowUpBuilder;

final readonly class BackfillShowcaseCollectionFollowUps
{
    public function __construct(
        private AssetHierarchy $hierarchy,
        private ShowcaseCollectionFollowUpBuilder $followUps,
    ) {}

    public function handle(): int
    {
        $generated = 0;

        ShowcaseDataset::query()
            ->whereNotIn('status', ['purging', 'purged'])
            ->orderBy('id')
            ->each(function (ShowcaseDataset $dataset) use (&$generated): void {
                $buildings = Asset::query()
                    ->with('portfolio')
                    ->whereNull('parent_id')
                    ->whereHas(
                        'portfolio',
                        fn ($portfolios) => $portfolios
                            ->where('showcase_dataset_id', $dataset->id),
                    )
                    ->orderBy('id')
                    ->get();

                foreach ($buildings as $buildingIndex => $building) {
                    $leases = Lease::query()
                        ->where('portfolio_id', $building->portfolio_id)
                        ->whereIn('leaseable_type', $this->hierarchy->leaseableTypes())
                        ->whereIn(
                            'leaseable_id',
                            $this->hierarchy->descendantIdsIncluding($building),
                        )
                        ->whereIn('status', ['active', 'expired', 'terminated'])
                        ->orderBy('code')
                        ->get()
                        ->values()
                        ->all();
                    $manager = User::query()
                        ->whereKey(collect($leases)->pluck('managed_by_user_id')->filter()->first())
                        ->first()
                        ?? User::query()->whereKey($building->portfolio?->owner_user_id)->first();

                    if (! $manager || ! $building->portfolio) {
                        continue;
                    }

                    $generated += $this->followUps->build(
                        $dataset,
                        $building->portfolio,
                        $manager,
                        $leases,
                        $buildingIndex,
                    );
                }
            });

        return $generated;
    }
}
