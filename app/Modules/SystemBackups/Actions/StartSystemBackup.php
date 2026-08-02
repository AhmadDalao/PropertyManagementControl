<?php

namespace App\Modules\SystemBackups\Actions;

use App\Models\SystemBackupRun;
use App\Models\User;
use App\Modules\SystemBackups\Support\SystemBackupAccess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

final readonly class StartSystemBackup
{
    public function __construct(private SystemBackupAccess $access) {}

    public function create(?User $initiator, string $trigger = 'manual'): SystemBackupRun
    {
        if ($initiator) {
            $this->access->ensureSuperadmin($initiator);
        }

        $lock = Cache::lock('system-backup-start', 30);

        if (! $lock->get()) {
            $this->alreadyRunning();
        }

        try {
            SystemBackupRun::query()
                ->whereIn('status', ['queued', 'running'])
                ->where('updated_at', '<', now()->subHours(2))
                ->update([
                    'status' => 'failed',
                    'failure_summary' => trans('app.backups.stale_run_failed'),
                    'failed_at' => now(),
                ]);

            if (SystemBackupRun::query()->whereIn('status', ['queued', 'running'])->exists()) {
                $this->alreadyRunning();
            }

            return SystemBackupRun::query()->create([
                'initiated_by_user_id' => $initiator?->id,
                'status' => 'queued',
                'trigger' => $trigger,
                'archive_disk' => 'local',
            ]);
        } finally {
            $lock->release();
        }
    }

    private function alreadyRunning(): never
    {
        throw ValidationException::withMessages([
            'backup' => trans('app.backups.already_running'),
        ]);
    }
}
