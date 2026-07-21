<?php

namespace App\Modules\Cms\Presenters;

use App\Models\CmsSection;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsOptions;
use App\Modules\Media\Queries\CmsMediaPickerQuery;

class CmsSectionFormPresenter
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsMediaPickerQuery $media,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ?CmsSection $section = null): array
    {
        $this->access->ensureAdmin($actor);

        return [
            'section' => $section,
            'sectionTypes' => CmsOptions::sectionTypes(),
            'mediaOptions' => $this->media->options($actor),
        ];
    }
}
