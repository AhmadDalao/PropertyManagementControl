<?php

namespace App\Modules\Documents\Data;

use App\Models\Document;
use App\Models\User;

final readonly class DocumentDetailData
{
    /** @param array{type:string,label:string,url:string}|null $attachment */
    public function __construct(
        public Document $document,
        public User $actor,
        public string $title,
        public ?array $attachment,
    ) {}
}
