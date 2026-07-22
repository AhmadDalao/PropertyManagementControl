<?php

namespace App\Modules\Media\Presenters;

use App\Models\MediaFile;

final class MediaFileTableRowPresenter
{
    /** @return array<string, mixed> */
    public function present(MediaFile $mediaFile): array
    {
        $mediaFile->loadMissing(['portfolio', 'uploadedBy']);

        return [
            'id' => $mediaFile->id,
            'title_en' => $mediaFile->title_en,
            'title_ar' => $mediaFile->title_ar,
            'alt_text_en' => $mediaFile->alt_text_en,
            'alt_text_ar' => $mediaFile->alt_text_ar,
            'filename' => basename((string) $mediaFile->path),
            'collection' => $mediaFile->collection,
            'visibility' => $mediaFile->visibility,
            'mime_type' => $mediaFile->mime_type,
            'size' => $mediaFile->size,
            'width' => $mediaFile->width,
            'height' => $mediaFile->height,
            'file_url' => route('media-files.file', $mediaFile),
            'created_at' => $mediaFile->created_at?->toDateTimeString(),
            'portfolio' => [
                'name_en' => $mediaFile->portfolio?->name_en,
                'name_ar' => $mediaFile->portfolio?->name_ar,
            ],
            'uploaded_by' => ['name' => $mediaFile->uploadedBy?->name],
        ];
    }
}
