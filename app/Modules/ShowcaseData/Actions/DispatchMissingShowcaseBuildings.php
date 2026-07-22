<?php

namespace App\Modules\ShowcaseData\Actions;

use App\Jobs\GenerateShowcaseBuilding;
use App\Models\ShowcaseDataset;
use App\Modules\ShowcaseData\Queries\ShowcaseDatasetMetrics;

class DispatchMissingShowcaseBuildings
{
    public function __construct(
        private readonly ShowcaseDatasetMetrics $metrics,
    ) {}

    public function handle(ShowcaseDataset $dataset): int
    {
        $missing = $this->metrics->missingBuildingIndexes($dataset);

        foreach ($missing as $buildingIndex) {
            GenerateShowcaseBuilding::dispatch($dataset->id, $buildingIndex);
        }

        return count($missing);
    }
}
