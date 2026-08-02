<?php

namespace App\Modules\Exports\Support\Docx;

final readonly class DocxDocument
{
    public function __construct(
        public string $xml,
        public string $title,
        public bool $rightToLeft,
        public int $usableWidth,
    ) {}
}
