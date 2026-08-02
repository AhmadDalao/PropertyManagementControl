<?php

namespace App\Modules\SystemBackups\Jobs;

use App\Models\SystemBackupRun;
use App\Modules\SystemBackups\Actions\CreateSystemBackup;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

final class CreateSystemBackupJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public int $uniqueFor = 1200;

    public function __construct(public readonly int $runId)
    {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return 'system-backup';
    }

    public function handle(CreateSystemBackup $backups): void
    {
        $backups->handle($this->runId);
    }

    public function failed(?Throwable $exception): void
    {
        $run = SystemBackupRun::query()->find($this->runId);

        if (! $run || $run->status === 'completed') {
            return;
        }

        $message = $exception?->getMessage();

        if (! is_string($message) || $message === '') {
            $translated = trans('app.backups.unknown_failure');
            $message = is_string($translated)
                ? $translated
                : 'The backup failed without a detailed server error.';
        }

        $run->update([
            'status' => 'failed',
            'failure_summary' => Str::limit(str_replace(
                [base_path(), storage_path()],
                ['[application]', '[storage]'],
                $message,
            ), 1000),
            'failed_at' => now(),
        ]);
    }
}
