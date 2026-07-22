<?php

namespace App\Modules\Media\Support;

final class MediaOptions
{
    /** @var array<int, string> */
    public const VISIBILITIES = ['public', 'private'];

    /** @var array<int, string> */
    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** @var array<int, string> */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public const MAX_FILE_KILOBYTES = 10240;

    public const MAX_DIMENSION = 10000;

    public static function diskFor(string $visibility): string
    {
        return $visibility === 'public' ? 'public' : 'local';
    }

    public static function publicUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }

    public static function directoryFor(?int $portfolioId): string
    {
        return $portfolioId === null
            ? 'media/website'
            : "media/portfolios/{$portfolioId}";
    }

    public static function label(string $visibility): string
    {
        $key = "app.media.{$visibility}";
        $label = trans($key);

        return is_string($label) && $label !== $key
            ? $label
            : str($visibility)->headline()->toString();
    }

    private function __construct() {}
}
