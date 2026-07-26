<?php

namespace App\Modules\ShowcaseData\Actions;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\ShowcaseDataset;
use App\Models\User;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\ShowcaseData\Generators\ShowcaseWorkOrderBuilder;

final readonly class BackfillShowcaseWorkOrders
{
    public function __construct(
        private AssetHierarchy $hierarchy,
        private ShowcaseWorkOrderBuilder $workOrders,
        private RefreshShowcaseDataset $refresh,
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
                    if (! $building->portfolio) {
                        continue;
                    }

                    $requests = array_values(MaintenanceRequest::query()
                        ->where('portfolio_id', $building->portfolio_id)
                        ->whereIn(
                            'asset_id',
                            $this->hierarchy->descendantIdsIncluding($building),
                        )
                        ->orderBy('id')
                        ->get()
                        ->all());
                    $manager = User::query()
                        ->whereKey(collect($requests)->pluck('assigned_to_user_id')->filter()->first())
                        ->first()
                        ?? User::query()->whereKey($building->portfolio->owner_user_id)->first();

                    if (! $manager) {
                        continue;
                    }

                    $generated += count($this->workOrders->build(
                        $building->portfolio,
                        $manager,
                        $requests,
                        $buildingIndex,
                    ));
                }

                $this->refresh->handle($dataset->id);
            });

        return $generated;
    }
}
