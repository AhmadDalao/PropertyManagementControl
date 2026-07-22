<?php

namespace App\Modules\ShowcaseData\Queries;

use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use Illuminate\Pagination\LengthAwarePaginator;

class ShowcaseDatasetIndexQuery
{
    /** @return LengthAwarePaginator<int, ShowcaseDataset> */
    public function paginate(): LengthAwarePaginator
    {
        return ShowcaseDataset::query()
            ->with('initiatedBy:id,name')
            ->latest('id')
            ->paginate(6)
            ->withQueryString();
    }

    /** @return array{datasets:int, active:int, complete:int, failed:int, live_buildings:int} */
    public function summary(): array
    {
        return [
            'datasets' => ShowcaseDataset::query()->count(),
            'active' => ShowcaseDataset::query()->whereIn('status', ['queued', 'generating', 'purging'])->count(),
            'complete' => ShowcaseDataset::query()->where('status', 'complete')->count(),
            'failed' => ShowcaseDataset::query()->where('status', 'failed')->count(),
            'live_buildings' => (int) ShowcaseDataset::query()
                ->whereNotIn('status', ['purged'])
                ->sum('generated_properties'),
        ];
    }

    public function canGenerate(): bool
    {
        return ! ShowcaseDataset::query()
            ->whereIn('status', ['queued', 'generating', 'purging'])
            ->exists();
    }

    public function legacyCandidates(): int
    {
        return Portfolio::query()
            ->whereNull('showcase_dataset_id')
            ->where('code', 'like', 'SHOW-%')
            ->count();
    }
}
