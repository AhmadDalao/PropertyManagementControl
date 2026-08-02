<?php

namespace App\Modules\DailyOperationsReports\Actions;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\DailyOperationsReports\Jobs\CreateDailyOperationsReportJob;

final readonly class QueueScheduledDailyOperationsReports
{
    public function __construct(private StartDailyOperationsReport $start) {}

    /** @return array{queued:int,skipped:int} */
    public function handle(): array
    {
        $date = today();
        $queued = 0;
        $skipped = 0;
        $superadmin = User::role('superadmin')
            ->where('status', 'active')
            ->oldest('id')
            ->first();

        if ($superadmin) {
            $run = $this->start->create(
                $superadmin,
                null,
                'scheduled',
                $date,
                $date->format('Y-m-d').'-global',
            );
            if ($run->status === 'queued' && $run->wasRecentlyCreated) {
                CreateDailyOperationsReportJob::dispatch($run->id);
                $queued++;
            }
        } else {
            $skipped++;
        }

        Portfolio::query()
            ->where('status', 'active')
            ->with('owner')
            ->orderBy('id')
            ->each(function (Portfolio $portfolio) use ($date, &$queued, &$skipped): void {
                $owner = $portfolio->owner;

                if (! $owner || $owner->status !== 'active' || ! $owner->hasRole('owner')) {
                    $skipped++;

                    return;
                }

                $run = $this->start->create(
                    $owner,
                    $portfolio->id,
                    'scheduled',
                    $date,
                    $date->format('Y-m-d').'-portfolio-'.$portfolio->id,
                );
                if ($run->status === 'queued' && $run->wasRecentlyCreated) {
                    CreateDailyOperationsReportJob::dispatch($run->id);
                    $queued++;
                }
            });

        return compact('queued', 'skipped');
    }
}
