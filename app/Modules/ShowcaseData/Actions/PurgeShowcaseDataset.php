<?php

namespace App\Modules\ShowcaseData\Actions;

use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PurgeShowcaseDataset
{
    public function handle(ShowcaseDataset $dataset): void
    {
        $dataset = DB::transaction(function () use ($dataset): ShowcaseDataset {
            $locked = ShowcaseDataset::query()->lockForUpdate()->findOrFail($dataset->id);
            abort_if(in_array($locked->status, ['purging', 'purged'], true), 422, trans('app.errors.not_allowed'));
            $locked->update(['status' => 'purging']);

            return $locked->fresh();
        }, 3);

        try {
            DB::transaction(function () use ($dataset): void {
                Portfolio::query()
                    ->where('showcase_dataset_id', $dataset->id)
                    ->each(fn (Portfolio $portfolio) => $portfolio->delete());
                User::query()
                    ->where('showcase_dataset_id', $dataset->id)
                    ->each(fn (User $user) => $user->delete());
            }, 3);

            if (! Storage::disk('local')->deleteDirectory("showcase/{$dataset->key}")) {
                throw new \RuntimeException('Could not remove the showcase document directory.');
            }

            ShowcaseDataset::query()->whereKey($dataset->id)->update([
                'status' => 'purged',
                'generated_properties' => 0,
                'counts_json' => [],
                'failure_details' => null,
                'purged_at' => now(),
            ]);
        } catch (Throwable $exception) {
            ShowcaseDataset::query()->whereKey($dataset->id)->update([
                'status' => 'failed',
                'failure_details' => Str::limit('Purge failed: '.$exception->getMessage(), 1200),
            ]);

            throw $exception;
        }
    }
}
