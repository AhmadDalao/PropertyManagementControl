<?php

namespace App\Modules\Media\Presenters;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Queries\MediaFileDetailQuery;
use App\Modules\Shared\ResourcePresenter;

final class MediaFileDetailPresenter
{
    public function __construct(
        private readonly MediaFileDetailQuery $details,
        private readonly MediaFileDetailHeaderPresenter $header,
        private readonly MediaFileDetailOverviewPresenter $overview,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(MediaFile $mediaFile, User $actor): array
    {
        $data = $this->details->get($mediaFile, $actor);
        $overview = $this->overview->present($data);

        return [
            'header' => $this->header->present($data),
            'spotlight' => $overview['spotlight'],
            'stats' => $overview['stats'],
            'sections' => $overview['sections'],
            'related' => [],
            'documents' => [],
            'timeline' => $this->resources->activityTimeline($data->mediaFile),
        ];
    }
}
