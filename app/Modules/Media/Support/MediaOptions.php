<?php

namespace App\Modules\Media\Support;

final class MediaOptions
{
    /** @var array<int, string> */
    public const VISIBILITIES = ['public', 'private'];

    /** @var array<int, string> */
    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public const MAX_FILE_KILOBYTES = 10240;

    public static function diskFor(string $visibility): string
    {
        return $visibility === 'public' ? 'public' : 'local';
    }

    public static function publicUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }
}
