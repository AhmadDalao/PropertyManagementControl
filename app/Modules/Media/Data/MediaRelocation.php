<?php

namespace App\Modules\Media\Data;

final readonly class MediaRelocation
{
    public function __construct(
        public string $sourceDisk,
        public string $sourcePath,
        public string $targetDisk,
        public string $targetPath,
    ) {}
}
