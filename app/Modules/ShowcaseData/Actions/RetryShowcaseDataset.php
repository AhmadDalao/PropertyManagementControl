<?php

namespace App\Modules\ShowcaseData\Actions;

use App\Models\ShowcaseDataset;
use App\Models\User;
use App\Modules\ShowcaseData\Support\ShowcaseAccess;
use Illuminate\Support\Facades\DB;

class RetryShowcaseDataset
{
    public function __construct(
        private readonly DispatchMissingShowcaseBuildings $dispatcher,
        private readonly RefreshShowcaseDataset $refresh,
        private readonly ShowcaseAccess $access,
    ) {}

    public function handle(User $actor, ShowcaseDataset $dataset): ShowcaseDataset
    {
        $this->access->ensureSuperadmin($actor);
        $dataset = DB::transaction(function () use ($dataset): ShowcaseDataset {
            $locked = ShowcaseDataset::query()->lockForUpdate()->findOrFail($dataset->id);
            $retryable = in_array($locked->status, ['failed', 'queued', 'generating'], true)
                || ($locked->status === 'complete' && $locked->generated_properties < $locked->target_properties);

            abort_unless($retryable, 422, trans('app.errors.not_allowed'));
            $locked->update([
                'status' => 'generating',
                'failure_details' => null,
                'completed_at' => null,
            ]);

            return $locked->fresh();
        }, 3);

        if ($this->dispatcher->handle($dataset) === 0) {
            return $this->refresh->handle($dataset->id);
        }

        return $dataset->fresh();
    }
}
