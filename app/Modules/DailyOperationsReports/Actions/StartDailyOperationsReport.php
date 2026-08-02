<?php

namespace App\Modules\DailyOperationsReports\Actions;

use App\Models\DailyOperationsReportRun;
use App\Models\User;
use App\Modules\DailyOperationsReports\Support\DailyOperationsReportAccess;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

final readonly class StartDailyOperationsReport
{
    public function __construct(private DailyOperationsReportAccess $access) {}

    public function create(
        User $initiator,
        ?int $requestedPortfolioId = null,
        string $trigger = 'manual',
        ?CarbonInterface $reportDate = null,
        ?string $scheduleKey = null,
    ): DailyOperationsReportRun {
        $portfolioId = $this->access->portfolioId($initiator, $requestedPortfolioId);
        $scopeKey = $portfolioId ? 'portfolio-'.$portfolioId : 'global';
        $lock = Cache::lock('daily-operations-report-start-'.$scopeKey, 30);

        if (! $lock->get()) {
            $this->alreadyRunning();
        }

        try {
            DailyOperationsReportRun::query()
                ->where('portfolio_id', $portfolioId)
                ->whereIn('status', ['queued', 'running'])
                ->where('updated_at', '<', now()->subHours(2))
                ->update([
                    'status' => 'failed',
                    'failure_summary' => trans('app.daily_reports.stale_run_failed'),
                    'failed_at' => now(),
                ]);

            if ($scheduleKey) {
                $scheduled = DailyOperationsReportRun::query()
                    ->where('schedule_key', $scheduleKey)
                    ->first();

                if ($scheduled) {
                    return $scheduled;
                }
            }

            if (DailyOperationsReportRun::query()
                ->where('portfolio_id', $portfolioId)
                ->whereIn('status', ['queued', 'running'])
                ->exists()
            ) {
                $this->alreadyRunning();
            }

            return DailyOperationsReportRun::query()->create([
                'portfolio_id' => $portfolioId,
                'initiated_by_user_id' => $initiator->id,
                'status' => 'queued',
                'trigger' => $trigger,
                'report_date' => ($reportDate ?? today())->toDateString(),
                'schedule_key' => $scheduleKey,
                'storage_disk' => 'local',
            ]);
        } finally {
            $lock->release();
        }
    }

    private function alreadyRunning(): never
    {
        throw ValidationException::withMessages([
            'report' => trans('app.daily_reports.already_running'),
        ]);
    }
}
