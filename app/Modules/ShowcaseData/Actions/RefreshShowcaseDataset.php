<?php

namespace App\Modules\ShowcaseData\Actions;

use App\Models\ShowcaseDataset;
use App\Modules\ShowcaseData\Queries\ShowcaseDatasetMetrics;
use Illuminate\Support\Facades\DB;

class RefreshShowcaseDataset
{
    public function __construct(
        private readonly ShowcaseDatasetMetrics $metrics,
    ) {}

    public function handle(int $datasetId): ShowcaseDataset
    {
        return DB::transaction(function () use ($datasetId): ShowcaseDataset {
            $dataset = ShowcaseDataset::query()->lockForUpdate()->findOrFail($datasetId);

            if (in_array($dataset->status, ['purging', 'purged'], true)) {
                return $dataset;
            }

            $generated = $this->metrics->generatedBuildings($dataset);
            $complete = $generated >= $dataset->target_properties;
            $dataset->update([
                'generated_properties' => $generated,
                'counts_json' => $this->metrics->counts($dataset),
                'status' => $complete ? 'complete' : 'generating',
                'completed_at' => $complete ? now() : null,
            ]);

            return $dataset->fresh();
        }, 3);
    }
}
