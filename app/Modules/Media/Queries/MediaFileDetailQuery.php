<?php

namespace App\Modules\Media\Queries;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Data\MediaFileDetailData;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Media\Support\MediaUsage;
use App\Modules\Shared\ResourcePresenter;

final class MediaFileDetailQuery
{
    public function __construct(
        private readonly MediaAccess $access,
        private readonly MediaUsage $usage,
        private readonly ResourcePresenter $resources,
    ) {}

    public function get(MediaFile $mediaFile, User $actor): MediaFileDetailData
    {
        $this->access->ensureCanManage($actor, $mediaFile);
        $mediaFile->loadMissing(['portfolio', 'uploadedBy']);
        $title = $this->resources->localized($mediaFile->title_en, $mediaFile->title_ar)
            ?: basename((string) $mediaFile->path);

        return new MediaFileDetailData(
            mediaFile: $mediaFile,
            actor: $actor,
            title: $title,
            alt: $this->resources->localized($mediaFile->alt_text_en, $mediaFile->alt_text_ar) ?: $title,
            fileUrl: route('media-files.file', $mediaFile),
            usageCount: $this->usage->cmsSectionCount($mediaFile),
        );
    }
}
