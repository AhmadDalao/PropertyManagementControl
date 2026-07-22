<?php

namespace App\Modules\ShowcaseData\Actions;

use App\Models\ShowcaseDataset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordShowcaseFailure
{
    public function handle(int $datasetId, int $buildingIndex, string $message): void
    {
        DB::transaction(function () use ($datasetId, $buildingIndex, $message): void {
            $dataset = ShowcaseDataset::query()->lockForUpdate()->find($datasetId);

            if (! $dataset || in_array($dataset->status, ['complete', 'purging', 'purged'], true)) {
                return;
            }

            $failure = 'Building '.($buildingIndex + 1).': '.Str::limit($message, 600);
            $details = trim(($dataset->failure_details ? $dataset->failure_details."\n" : '').$failure);
            $dataset->update([
                'status' => 'failed',
                'failure_details' => Str::limit($details, 12_000),
            ]);
        }, 3);
    }
}
