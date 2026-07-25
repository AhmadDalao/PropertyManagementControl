<?php

namespace App\Modules\SystemReadiness\Actions;

use App\Modules\SystemReadiness\Support\SchedulerHeartbeatHistory;
use Illuminate\Support\Facades\Cache;

final class RecordSchedulerHeartbeat
{
    public const CACHE_KEY = 'system-readiness.scheduler-heartbeat';

    public function handle(): void
    {
        $history = SchedulerHeartbeatHistory::from(Cache::get(self::CACHE_KEY))
            ->record(now()->toImmutable());

        Cache::forever(self::CACHE_KEY, $history->toCacheValue());
    }
}
