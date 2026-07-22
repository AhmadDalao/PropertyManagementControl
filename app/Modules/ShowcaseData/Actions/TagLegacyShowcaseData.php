<?php

namespace App\Modules\ShowcaseData\Actions;

use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\User;
use App\Modules\ShowcaseData\Queries\ShowcaseDatasetMetrics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TagLegacyShowcaseData
{
    public function __construct(
        private readonly ShowcaseDatasetMetrics $metrics,
    ) {}

    public function handle(): ?ShowcaseDataset
    {
        return DB::transaction(function (): ?ShowcaseDataset {
            $legacyPortfolios = Portfolio::query()
                ->whereNull('showcase_dataset_id')
                ->where('code', 'like', 'SHOW-%')
                ->lockForUpdate()
                ->get();

            if ($legacyPortfolios->isEmpty()) {
                return null;
            }

            $dataset = ShowcaseDataset::query()->firstOrCreate(
                ['key' => 'LEGACY-SHOWCASE'],
                [
                    'name' => trans('app.showcase.legacy_name'),
                    'status' => 'complete',
                    'target_properties' => 0,
                    'generated_properties' => 0,
                    'started_at' => now(),
                    'completed_at' => now(),
                ],
            );

            foreach ($legacyPortfolios as $portfolio) {
                $portfolio->update(['showcase_dataset_id' => $dataset->id]);
                User::query()
                    ->where('portfolio_id', $portfolio->id)
                    ->get()
                    ->each(function (User $user) use ($dataset): void {
                        $user->update([
                            'showcase_dataset_id' => $dataset->id,
                            'status' => 'inactive',
                            'email' => "legacy-{$user->id}@showcase.invalid",
                            'password' => Hash::make(Str::password(40)),
                        ]);
                    });
            }

            $total = $dataset->portfolios()->count();
            $dataset->update([
                'status' => 'complete',
                'target_properties' => $total,
                'generated_properties' => $total,
                'counts_json' => $this->metrics->counts($dataset),
                'failure_details' => null,
                'completed_at' => now(),
                'purged_at' => null,
            ]);

            return $dataset->fresh();
        }, 3);
    }
}
