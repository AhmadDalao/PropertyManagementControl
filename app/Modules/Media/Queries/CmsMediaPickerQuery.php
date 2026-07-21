<?php

namespace App\Modules\Media\Queries;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Media\Support\MediaOptions;

class CmsMediaPickerQuery
{
    private const LIMIT = 100;

    public function __construct(private readonly MediaAccess $access) {}

    /** @return array<int, array<string, mixed>> */
    public function options(User $actor): array
    {
        $this->access->ensureGlobalManager($actor);

        return MediaFile::query()
            ->select([
                'id',
                'title_en',
                'title_ar',
                'alt_text_en',
                'alt_text_ar',
                'path',
                'width',
                'height',
            ])
            ->whereNull('portfolio_id')
            ->where('visibility', 'public')
            ->where('disk', 'public')
            ->whereIn('mime_type', MediaOptions::MIME_TYPES)
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (MediaFile $mediaFile): array => [
                'id' => $mediaFile->id,
                'title_en' => $mediaFile->title_en,
                'title_ar' => $mediaFile->title_ar,
                'alt_text_en' => $mediaFile->alt_text_en,
                'alt_text_ar' => $mediaFile->alt_text_ar,
                'url' => MediaOptions::publicUrl($mediaFile->path),
                'width' => $mediaFile->width,
                'height' => $mediaFile->height,
            ])
            ->all();
    }
}
