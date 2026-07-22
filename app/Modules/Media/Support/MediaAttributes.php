<?php

namespace App\Modules\Media\Support;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Data\MediaRelocation;
use App\Modules\Media\Data\StoredMediaImage;

final class MediaAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forCreate(
        User $actor,
        ?int $portfolioId,
        StoredMediaImage $file,
        array $data,
    ): array {
        return [
            'uploaded_by_user_id' => $actor->id,
            'portfolio_id' => $portfolioId,
            'collection' => $data['collection'],
            'disk' => $file->disk,
            'path' => $file->path,
            'mime_type' => $file->mimeType,
            'size' => $file->size,
            'width' => $file->width,
            'height' => $file->height,
            ...$this->metadata($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forUpdate(
        MediaFile $mediaFile,
        ?int $portfolioId,
        ?MediaRelocation $relocation,
        array $data,
    ): array {
        return [
            'portfolio_id' => $portfolioId,
            'collection' => $data['collection'],
            'disk' => $relocation instanceof MediaRelocation ? $relocation->targetDisk : $mediaFile->disk,
            'path' => $relocation instanceof MediaRelocation ? $relocation->targetPath : $mediaFile->path,
            ...$this->metadata($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function metadata(array $data): array
    {
        return [
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'alt_text_en' => $data['alt_text_en'],
            'alt_text_ar' => $data['alt_text_ar'],
            'visibility' => $data['visibility'],
        ];
    }
}
