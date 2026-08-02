<?php

namespace App\Modules\SystemBackups\Support;

use App\Modules\SystemBackups\Contracts\DocumentBackupWriter;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final readonly class TarDocumentBackupWriter implements DocumentBackupWriter
{
    public function __construct(private TarArchive $archives) {}

    public function write(string $outputPath): array
    {
        $source = storage_path('app/private');
        $backupRoot = $source.DIRECTORY_SEPARATOR.'system-backups';
        $fileCount = 0;
        $sourceBytes = 0;

        File::ensureDirectoryExists($source);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (
                ! $file->isFile()
                || str_starts_with($file->getPathname(), $backupRoot.DIRECTORY_SEPARATOR)
            ) {
                continue;
            }

            $fileCount++;
            $sourceBytes += $file->getSize();
        }

        $this->archives->create(
            $outputPath,
            $source,
            ['.'],
            ['system-backups'],
        );

        return [
            'file_count' => $fileCount,
            'source_bytes' => $sourceBytes,
            'archive_bytes' => File::size($outputPath),
            'sha256' => $this->checksum($outputPath),
        ];
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
