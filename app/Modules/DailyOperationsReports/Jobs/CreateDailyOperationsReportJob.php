<?php

namespace App\Modules\DailyOperationsReports\Jobs;

use App\Models\DailyOperationsReportRun;
use App\Modules\DailyOperationsReports\Actions\CreateDailyOperationsReport;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

final class CreateDailyOperationsReportJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 90;

    public int $uniqueFor = 300;

    public function __construct(public readonly int $runId)
    {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return 'daily-operations-report-'.$this->runId;
    }

    public function handle(CreateDailyOperationsReport $reports): void
    {
        $reports->handle($this->runId);
    }

    public function failed(?Throwable $exception): void
    {
        $run = DailyOperationsReportRun::query()->find($this->runId);

        if (! $run || $run->status === 'completed') {
            return;
        }

        $run->update([
            'status' => 'failed',
            'failure_summary' => Str::limit(
                $exception?->getMessage() ?: trans('app.daily_reports.unknown_failure'),
                1000,
            ),
            'failed_at' => now(),
        ]);
    }
}
