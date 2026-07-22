<?php

namespace App\Modules\Documents\Data;

use App\Models\Document;
use App\Models\User;

final readonly class DocumentFormData
{
    /**
     * @param  array<string, mixed>  $defaults
     * @param  array{type:string,label:string,url:string}|null  $attachment
     */
    public function __construct(
        public User $actor,
        public ?Document $document,
        public array $defaults,
        public string $attachmentAlias,
        public ?int $attachmentId,
        public string $type,
        public ?array $attachment,
    ) {}
}
