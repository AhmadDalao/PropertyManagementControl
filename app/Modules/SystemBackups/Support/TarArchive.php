<?php

namespace App\Modules\SystemBackups\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class TarArchive
{
    /**
     * @param  list<string>  $entries
     * @param  list<string>  $excludes
     */
    public function create(
        string $outputPath,
        string $sourceDirectory,
        array $entries,
        array $excludes = [],
    ): void {
        $binary = (string) config('operations.tar_binary', '/usr/bin/tar');

        if (! is_executable($binary)) {
            throw new RuntimeException(trans('app.backups.tar_unavailable'));
        }

        File::ensureDirectoryExists(dirname($outputPath));
        File::delete($outputPath);

        $command = [$binary, '-czf', $outputPath];

        foreach ($excludes as $exclude) {
            $command[] = '--exclude='.$exclude;
        }

        array_push($command, '-C', $sourceDirectory, ...$entries);

        $pipes = [];
        $process = proc_open(
            $command,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            base_path(),
        );

        if (! is_resource($process)) {
            throw new RuntimeException(trans('app.backups.archive_start_failed'));
        }

        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || ! File::isFile($outputPath) || File::size($outputPath) === 0) {
            $detail = trim($stderr !== '' ? $stderr : $stdout);

            throw new RuntimeException(trans('app.backups.archive_failed', [
                'detail' => $detail !== '' ? mb_substr($detail, -500) : trans('app.backups.no_archive_output'),
            ]));
        }
    }
}
