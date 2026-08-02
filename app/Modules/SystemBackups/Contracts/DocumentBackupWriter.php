<?php

namespace App\Modules\SystemBackups\Contracts;

interface DocumentBackupWriter
{
    /**
     * @return array{file_count:int,source_bytes:int,archive_bytes:int,sha256:string}
     */
    public function write(string $outputPath): array;
}
