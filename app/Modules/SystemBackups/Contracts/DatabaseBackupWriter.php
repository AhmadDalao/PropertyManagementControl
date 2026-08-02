<?php

namespace App\Modules\SystemBackups\Contracts;

interface DatabaseBackupWriter
{
    /**
     * @return array{table_count:int,row_count:int,bytes:int,sha256:string}
     */
    public function write(string $outputPath): array;
}
