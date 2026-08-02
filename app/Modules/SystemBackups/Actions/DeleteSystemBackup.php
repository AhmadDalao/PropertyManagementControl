<?php

namespace App\Modules\SystemBackups\Actions;

use App\Models\SystemBackupRun;
use App\Models\User;
use App\Modules\SystemBackups\Support\SystemBackupAccess;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final readonly class DeleteSystemBackup
{
    public function __construct(private SystemBackupAccess $access) {}

    public function handle(User $actor, SystemBackupRun $run): SystemBackupRun
    {
        $this->access->ensureSuperadmin($actor);

        if (in_array($run->status, ['queued', 'running'], true)) {
            throw ValidationException::withMessages([
                'backup' => trans('app.backups.active_cannot_prune'),
            ]);
        }

        if ($run->archive_path) {
            Storage::disk($run->archive_disk)->delete($run->archive_path);
        }

        $run->update([
            'status' => 'pruned',
            'archive_path' => null,
            'meta_json' => [
                ...($run->meta_json ?? []),
                'pruned_at' => now()->toIso8601String(),
                'pruned_by_user_id' => $actor->id,
            ],
        ]);

        return $run->refresh();
    }
}
