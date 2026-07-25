<?php

namespace App\Modules\Maintenance\Support;

final class MaintenanceAttachmentOptions
{
    /** @var array<int, string> */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** @var array<int, string> */
    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public const MAX_FILES_PER_UPLOAD = 4;

    public const MAX_FILES_PER_REQUEST = 12;

    public const MAX_FILE_KILOBYTES = 5120;

    public const MAX_DIMENSION = 8000;

    public static function directory(int $portfolioId, int $requestId): string
    {
        return "maintenance/attachments/{$portfolioId}/{$requestId}";
    }

    private function __construct() {}
}
