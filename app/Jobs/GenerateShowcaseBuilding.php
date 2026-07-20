<?php

namespace App\Jobs;

use App\Services\ShowcaseDatasetService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateShowcaseBuilding implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    public int $uniqueFor = 600;

    public function __construct(
        public readonly int $datasetId,
        public readonly int $buildingIndex,
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return "{$this->datasetId}:{$this->buildingIndex}";
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(ShowcaseDatasetService $service): void
    {
        $service->generateBuilding($this->datasetId, $this->buildingIndex);
    }

    public function failed(?Throwable $exception): void
    {
        app(ShowcaseDatasetService::class)->recordFailure(
            $this->datasetId,
            $this->buildingIndex,
            $exception?->getMessage() ?? 'Unknown queue failure.',
        );
    }
}
