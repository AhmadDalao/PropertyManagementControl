<?php

namespace App\Modules\Media\Data;

final readonly class StoredMediaImage
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $mimeType,
        public int $size,
        public int $width,
        public int $height,
    ) {}
}
