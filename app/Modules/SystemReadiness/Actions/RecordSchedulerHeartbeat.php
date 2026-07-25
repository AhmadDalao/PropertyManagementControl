<?php

namespace App\Modules\SystemReadiness\Actions;

use Illuminate\Support\Facades\Cache;

final class RecordSchedulerHeartbeat
{
    public const CACHE_KEY = 'system-readiness.scheduler-heartbeat';

    public function handle(): void
    {
        Cache::forever(self::CACHE_KEY, now()->toIso8601String());
    }
}
