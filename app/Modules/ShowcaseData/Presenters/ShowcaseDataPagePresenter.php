<?php

namespace App\Modules\ShowcaseData\Presenters;

use App\Models\ShowcaseDataset;
use App\Modules\ShowcaseData\Queries\ShowcaseDatasetIndexQuery;
use App\Modules\ShowcaseData\Support\ShowcaseTargets;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class ShowcaseDataPagePresenter
{
    public function __construct(
        private readonly ShowcaseDatasetIndexQuery $datasets,
        private readonly ShowcaseTargets $targets,
    ) {}

    /** @return array<string, mixed> */
    public function present(): array
    {
        $datasets = $this->datasets->paginate();
        $datasets->through(fn (ShowcaseDataset $dataset): array => $this->dataset($dataset));

        return [
            'datasets' => $datasets,
            'summary' => $this->datasets->summary(),
            'targets' => $this->targets->all(),
            'canGenerate' => $this->datasets->canGenerate(),
            'legacyCandidates' => $this->datasets->legacyCandidates(),
        ];
    }

    /** @return array<string, mixed> */
    private function dataset(ShowcaseDataset $dataset): array
    {
        $counts = $dataset->getAttribute('counts_json');
        $target = max(0, (int) $dataset->target_properties);
        $generated = max(0, (int) $dataset->generated_properties);

        return [
            'id' => $dataset->id,
            'key' => $dataset->key,
            'name' => $dataset->name,
            'status' => $dataset->status,
            'target_properties' => $target,
            'generated_properties' => $generated,
            'progress_percent' => $target > 0
                ? min(100, round(($generated / $target) * 100, 1))
                : 0,
            'counts' => $this->numericCounts(is_array($counts) ? $counts : []),
            'failure_details' => $dataset->failure_details,
            'initiated_by' => $this->relatedName($dataset->getRelation('initiatedBy')),
            'started_at' => $this->isoDate($dataset->started_at),
            'completed_at' => $this->isoDate($dataset->completed_at),
            'purged_at' => $this->isoDate($dataset->purged_at),
            'can_retry' => $dataset->status === 'failed'
                || ($dataset->status === 'complete' && $generated < $target),
            'can_purge' => ! in_array($dataset->status, ['purging', 'purged'], true),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $counts
     * @return array<string, int>
     */
    private function numericCounts(array $counts): array
    {
        $normalized = [];

        foreach ($counts as $key => $value) {
            if (is_string($key) && is_numeric($value)) {
                $normalized[$key] = (int) $value;
            }
        }

        return $normalized;
    }

    private function relatedName(mixed $model): ?string
    {
        if (! $model instanceof Model) {
            return null;
        }

        $name = $model->getAttribute('name');

        return is_string($name) ? $name : null;
    }

    private function isoDate(mixed $value): ?string
    {
        return $value instanceof CarbonInterface ? $value->toIso8601String() : null;
    }
}
