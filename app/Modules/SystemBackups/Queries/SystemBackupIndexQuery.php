<?php

namespace App\Modules\SystemBackups\Queries;

use App\Models\SystemBackupRun;
use App\Models\User;
use App\Modules\SystemBackups\Requests\SystemBackupIndexRequest;
use App\Modules\SystemBackups\Support\SystemBackupAccess;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Throwable;

final readonly class SystemBackupIndexQuery
{
    public function __construct(private SystemBackupAccess $access) {}

    /** @return array<string, mixed> */
    public function handle(SystemBackupIndexRequest $request, User $actor): array
    {
        $this->access->ensureSuperadmin($actor);
        $status = $request->status();
        $query = SystemBackupRun::query()
            ->with('initiatedBy:id,name')
            ->latest('id');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        /** @var LengthAwarePaginator<int, SystemBackupRun> $runs */
        $runs = $query->paginate(12)->withQueryString();
        $runs->through(fn (SystemBackupRun $run): array => $this->record($run));
        $latest = SystemBackupRun::query()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        return [
            'backups' => $runs,
            'filters' => ['status' => $status],
            'summary' => [
                'completed' => SystemBackupRun::query()->where('status', 'completed')->count(),
                'failed' => SystemBackupRun::query()->where('status', 'failed')->count(),
                'active' => SystemBackupRun::query()->whereIn('status', ['queued', 'running'])->count(),
                'stored_bytes' => (int) SystemBackupRun::query()
                    ->where('status', 'completed')
                    ->sum('archive_bytes'),
                'latest_completed_at' => $latest?->completed_at?->toIso8601String(),
                'retention_count' => max(1, (int) config('operations.backup_retention_count', 7)),
            ],
            'statusOptions' => collect(['all', 'queued', 'running', 'completed', 'failed', 'pruned'])
                ->map(fn (string $value): array => [
                    'value' => $value,
                    'label' => trans("app.backups.statuses.{$value}"),
                ])
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function record(SystemBackupRun $run): array
    {
        $archiveAvailable = false;

        if ($run->status === 'completed' && $run->archive_path !== null) {
            try {
                $archiveAvailable = Storage::disk($run->archive_disk)
                    ->exists($run->archive_path);
            } catch (Throwable) {
                $archiveAvailable = false;
            }
        }

        return [
            'id' => $run->id,
            'status' => $run->status,
            'status_label' => trans("app.backups.statuses.{$run->status}"),
            'trigger' => $run->trigger,
            'trigger_label' => trans("app.backups.triggers.{$run->trigger}"),
            'initiated_by' => $run->initiatedBy?->name,
            'database_bytes' => $run->database_bytes,
            'documents_bytes' => $run->documents_bytes,
            'archive_bytes' => $run->archive_bytes,
            'table_count' => $run->table_count,
            'database_row_count' => $run->database_row_count,
            'document_count' => $run->document_count,
            'archive_sha256' => $run->archive_sha256,
            'failure_summary' => $run->failure_summary,
            'created_at' => $run->created_at?->toIso8601String(),
            'started_at' => $run->started_at?->toIso8601String(),
            'completed_at' => $run->completed_at?->toIso8601String(),
            'failed_at' => $run->failed_at?->toIso8601String(),
            'archive_available' => $archiveAvailable,
            'can_download' => $archiveAvailable,
            'can_prune' => ! in_array($run->status, ['queued', 'running', 'pruned'], true),
            'download_url' => route('system-backups.download', $run),
            'prune_url' => route('system-backups.destroy', $run),
        ];
    }
}
