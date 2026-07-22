<?php

namespace App\Jobs;

use App\Modules\ShowcaseData\Actions\BuildShowcaseProperty;
use App\Modules\ShowcaseData\Actions\RecordShowcaseFailure;
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

    public function handle(BuildShowcaseProperty $builder): void
    {
        $builder->handle($this->datasetId, $this->buildingIndex);
    }

    public function failed(?Throwable $exception): void
    {
        app(RecordShowcaseFailure::class)->handle(
            $this->datasetId,
            $this->buildingIndex,
            $exception?->getMessage() ?? 'Unknown queue failure.',
        );
    }
}
