<?php

namespace App\Modules\SystemBackups\Actions;

use App\Models\SystemBackupRun;
use Illuminate\Support\Facades\Storage;

final class PruneSystemBackups
{
    public function handle(): int
    {
        $retention = max(1, (int) config('operations.backup_retention_count', 7));
        $pruned = 0;

        SystemBackupRun::query()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->latest('id')
            ->offset($retention)
            ->limit(PHP_INT_MAX)
            ->get()
            ->each(function (SystemBackupRun $run) use (&$pruned): void {
                if ($run->archive_path) {
                    Storage::disk($run->archive_disk)->delete($run->archive_path);
                }

                $run->update([
                    'status' => 'pruned',
                    'archive_path' => null,
                    'meta_json' => [
                        ...($run->meta_json ?? []),
                        'pruned_at' => now()->toIso8601String(),
                        'pruned_by' => 'retention',
                    ],
                ]);
                $pruned++;
            });

        return $pruned;
    }
}
