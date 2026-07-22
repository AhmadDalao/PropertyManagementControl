<?php

namespace App\Modules\Media\Data;

use App\Models\MediaFile;
use App\Models\User;

final readonly class MediaFileFormData
{
    /**
     * @param  array<int, array{id:int,name:string}>  $portfolioOptions
     * @param  array<string, mixed>  $defaults
     */
    public function __construct(
        public User $actor,
        public ?MediaFile $mediaFile,
        public array $portfolioOptions,
        public array $defaults,
    ) {}
}
