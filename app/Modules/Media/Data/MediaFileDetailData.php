<?php

namespace App\Modules\Media\Data;

use App\Models\MediaFile;
use App\Models\User;

final readonly class MediaFileDetailData
{
    public function __construct(
        public MediaFile $mediaFile,
        public User $actor,
        public string $title,
        public string $alt,
        public string $fileUrl,
        public int $usageCount,
    ) {}
}
