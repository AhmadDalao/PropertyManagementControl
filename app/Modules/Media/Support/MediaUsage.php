<?php

namespace App\Modules\Media\Support;

use App\Models\CmsSection;
use App\Models\MediaFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class MediaUsage
{
    public function cmsSectionCount(MediaFile $mediaFile): int
    {
        $references = $this->references($mediaFile);

        if ($references === []) {
            return 0;
        }

        return CmsSection::query()
            ->where(function (Builder $query) use ($references): void {
                foreach ($references as $reference) {
                    $like = '%'.$this->escapeLike($reference).'%';
                    $escapedLike = '%'.$this->escapeLike(str_replace('/', '\\/', $reference)).'%';
                    $query->orWhere('content_en->image', $reference)
                        ->orWhere('content_ar->image', $reference)
                        ->orWhere('content_en', 'like', $like)
                        ->orWhere('content_ar', 'like', $like)
                        ->orWhere('content_en', 'like', $escapedLike)
                        ->orWhere('content_ar', 'like', $escapedLike);
                }
            })
            ->count();
    }

    public function ensureUnused(MediaFile $mediaFile): void
    {
        if ($this->cmsSectionCount($mediaFile) > 0) {
            throw ValidationException::withMessages([
                'media' => trans('app.errors.media_in_use'),
            ]);
        }
    }

    /** @return array<int, string> */
    private function references(MediaFile $mediaFile): array
    {
        if ($mediaFile->path === '') {
            return [];
        }

        return array_values(array_unique([
            MediaOptions::publicUrl($mediaFile->path),
            Storage::disk('public')->url($mediaFile->path),
        ]));
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
