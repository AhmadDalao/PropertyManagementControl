<?php

namespace App\Modules\Media\Presenters;

use App\Modules\Media\Data\MediaFileDetailData;
use App\Modules\Media\Support\MediaOptions;

final class MediaFileDetailHeaderPresenter
{
    /** @return array<string, mixed> */
    public function present(MediaFileDetailData $data): array
    {
        $mediaFile = $data->mediaFile;

        return [
            'eyebrow' => trans('app.media.detail_eyebrow'),
            'title' => $data->title,
            'description' => trans('app.media.detail_description', [
                'collection' => $mediaFile->collection,
                'visibility' => MediaOptions::label($mediaFile->visibility),
            ]),
            'backHref' => route('media-files.index'),
            'backLabel' => trans('app.media.all_media'),
            'actions' => [
                ['label' => trans('app.media.edit_media'), 'href' => route('media-files.edit', $mediaFile), 'variant' => 'primary'],
                ['label' => trans('app.media.open_image'), 'href' => $data->fileUrl, 'variant' => 'secondary', 'external' => true],
            ],
        ];
    }
}
