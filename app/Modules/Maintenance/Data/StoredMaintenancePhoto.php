<?php

namespace App\Modules\Maintenance\Data;

final readonly class StoredMaintenancePhoto
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $originalName,
        public string $mimeType,
        public int $size,
        public int $width,
        public int $height,
    ) {}
}
