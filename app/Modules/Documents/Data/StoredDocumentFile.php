<?php

namespace App\Modules\Documents\Data;

final readonly class StoredDocumentFile
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $originalName,
        public string $mimeType,
        public int $size,
    ) {}
}
