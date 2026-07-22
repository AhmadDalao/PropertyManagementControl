<?php

namespace App\Modules\Media\Actions;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Support\MediaAccess;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MediaFileResponse
{
    public function __construct(private readonly MediaAccess $access) {}

    public function inline(User $actor, MediaFile $mediaFile): StreamedResponse
    {
        $this->access->ensureCanManage($actor, $mediaFile);
        abort_unless(
            Storage::disk($mediaFile->disk)->exists($mediaFile->path),
            404,
            trans('app.errors.media_file_missing'),
        );
        $title = trim((string) ($mediaFile->title_en ?: $mediaFile->title_ar ?: 'media'));
        $extension = pathinfo($mediaFile->path, PATHINFO_EXTENSION) ?: 'image';
        $filename = (Str::slug($title) ?: 'media').'.'.$extension;

        return Storage::disk($mediaFile->disk)->response(
            $mediaFile->path,
            $filename,
            ['Content-Type' => (string) $mediaFile->mime_type],
            'inline',
        );
    }
}
