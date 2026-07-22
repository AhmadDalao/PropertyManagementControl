<?php

namespace App\Modules\ShowcaseData\Actions;

use App\Models\ShowcaseDataset;
use App\Models\User;
use App\Modules\ShowcaseData\Generators\ShowcaseFoundationBuilder;
use App\Modules\ShowcaseData\Queries\ShowcaseDatasetMetrics;
use App\Modules\ShowcaseData\Support\ShowcaseTargets;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StartShowcaseDataset
{
    public function __construct(
        private readonly TagLegacyShowcaseData $legacy,
        private readonly ShowcaseFoundationBuilder $foundation,
        private readonly ShowcaseDatasetMetrics $metrics,
        private readonly DispatchMissingShowcaseBuildings $dispatcher,
    ) {}

    public function handle(User $initiator): ShowcaseDataset
    {
        $lock = Cache::lock('showcase-dataset-start', 180);

        if (! $lock->get()) {
            $this->alreadyRunning();
        }

        try {
            if ($this->activeDatasetExists()) {
                $this->alreadyRunning();
            }

            $this->legacy->handle();
            $dataset = DB::transaction(function () use ($initiator): ShowcaseDataset {
                $dataset = ShowcaseDataset::query()->create([
                    'key' => 'SHOWCASE-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(5)),
                    'name' => 'Property operations showcase '.now()->format('Y-m-d H:i'),
                    'status' => 'generating',
                    'target_properties' => ShowcaseTargets::BUILDINGS,
                    'generated_properties' => 0,
                    'counts_json' => [],
                    'initiated_by_user_id' => $initiator->id,
                    'started_at' => now(),
                ]);

                $this->foundation->build($dataset);
                $dataset->update(['counts_json' => $this->metrics->counts($dataset)]);

                return $dataset->fresh();
            }, 3);

            try {
                $this->dispatcher->handle($dataset);
            } catch (Throwable $exception) {
                $dataset->update([
                    'status' => 'failed',
                    'failure_details' => Str::limit('Queue dispatch: '.$exception->getMessage(), 1200),
                ]);

                throw $exception;
            }

            return $dataset->fresh();
        } finally {
            $lock->release();
        }
    }

    private function activeDatasetExists(): bool
    {
        return ShowcaseDataset::query()
            ->whereIn('status', ['queued', 'generating', 'purging'])
            ->exists();
    }

    private function alreadyRunning(): never
    {
        throw ValidationException::withMessages([
            'showcase' => trans('app.showcase.already_running'),
        ]);
    }
}
