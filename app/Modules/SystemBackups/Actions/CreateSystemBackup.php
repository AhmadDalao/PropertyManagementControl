<?php

namespace App\Modules\SystemBackups\Actions;

use App\Models\SystemBackupRun;
use App\Modules\SystemBackups\Contracts\DatabaseBackupWriter;
use App\Modules\SystemBackups\Contracts\DocumentBackupWriter;
use App\Modules\SystemBackups\Support\TarArchive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final readonly class CreateSystemBackup
{
    public function __construct(
        private DatabaseBackupWriter $database,
        private DocumentBackupWriter $documents,
        private TarArchive $archives,
        private PruneSystemBackups $prune,
    ) {}

    public function handle(int $runId): SystemBackupRun
    {
        $run = DB::transaction(function () use ($runId): SystemBackupRun {
            $locked = SystemBackupRun::query()->lockForUpdate()->findOrFail($runId);

            if ($locked->status !== 'queued') {
                throw new RuntimeException(trans('app.backups.run_not_queued'));
            }

            $locked->update([
                'status' => 'running',
                'started_at' => now(),
                'failed_at' => null,
                'failure_summary' => null,
            ]);

            return $locked->refresh();
        }, 3);

        $disk = Storage::disk('local');
        $backupDirectory = $disk->path('system-backups');
        $staging = $backupDirectory.'/.partial-'.$run->id.'-'.Str::lower(Str::random(8));
        $databasePath = $staging.'/database.sql.gz';
        $documentsPath = $staging.'/private-storage.tar.gz';
        $manifestPath = $staging.'/manifest.json';
        $archiveName = sprintf(
            'property-backup-%s-%06d.tar.gz',
            now()->format('Ymd-His'),
            $run->id,
        );
        $relativeArchive = 'system-backups/'.$archiveName;
        $finalArchive = $disk->path($relativeArchive);

        File::ensureDirectoryExists($staging);

        try {
            $database = $this->database->write($databasePath);
            $documents = $this->documents->write($documentsPath);
            $manifest = [
                'format_version' => 1,
                'application' => (string) config('app.name'),
                'created_at' => now()->toIso8601String(),
                'revision' => $this->revision(),
                'run_id' => $run->id,
                'database' => $database,
                'private_storage' => $documents,
            ];

            File::put(
                $manifestPath,
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
                true,
            );
            $this->archives->create(
                $staging.'/bundle.tar.gz',
                $staging,
                ['database.sql.gz', 'private-storage.tar.gz', 'manifest.json'],
            );
            File::ensureDirectoryExists($backupDirectory);

            if (! File::move($staging.'/bundle.tar.gz', $finalArchive)) {
                throw new RuntimeException(trans('app.backups.archive_move_failed'));
            }

            $run->update([
                'status' => 'completed',
                'archive_path' => $relativeArchive,
                'database_bytes' => $database['bytes'],
                'documents_bytes' => $documents['archive_bytes'],
                'archive_bytes' => File::size($finalArchive),
                'table_count' => $database['table_count'],
                'database_row_count' => $database['row_count'],
                'document_count' => $documents['file_count'],
                'archive_sha256' => $this->checksum($finalArchive),
                'meta_json' => [
                    'revision' => $manifest['revision'],
                    'database_sha256' => $database['sha256'],
                    'documents_sha256' => $documents['sha256'],
                    'document_source_bytes' => $documents['source_bytes'],
                ],
                'completed_at' => now(),
            ]);

            $this->prune->handle();

            return $run->refresh();
        } catch (Throwable $exception) {
            File::delete($finalArchive);
            $run->update([
                'status' => 'failed',
                'failure_summary' => $this->safeFailure($exception),
                'failed_at' => now(),
            ]);

            throw $exception;
        } finally {
            File::deleteDirectory($staging);
        }
    }

    private function revision(): ?string
    {
        $marker = storage_path('app/.deployed-revision');

        if (! File::isFile($marker)) {
            return null;
        }

        $revision = trim((string) File::get($marker));

        return preg_match('/\A[0-9a-f]{40}\z/', $revision) === 1 ? $revision : null;
    }

    private function safeFailure(Throwable $exception): string
    {
        return Str::limit(str_replace(
            [base_path(), storage_path()],
            ['[application]', '[storage]'],
            $exception->getMessage(),
        ), 1000);
    }

    private function checksum(string $path): string
    {
        $checksum = hash_file('sha256', $path);

        if (! is_string($checksum)) {
            throw new RuntimeException(trans('app.backups.checksum_failed'));
        }

        return $checksum;
    }
}
