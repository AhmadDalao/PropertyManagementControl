<?php

namespace App\Modules\Maintenance\Support;

final class MaintenanceAttachmentRules
{
    /** @return array<string, array<int, mixed>> */
    public static function optional(): array
    {
        return self::rules('nullable');
    }

    /** @return array<string, array<int, mixed>> */
    public static function required(): array
    {
        return self::rules('required');
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'photos' => trans('app.maintenance.photos'),
            'photos.*' => trans('app.maintenance.photo'),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private static function rules(string $presence): array
    {
        return [
            'photos' => [
                $presence,
                'array',
                'max:'.MaintenanceAttachmentOptions::MAX_FILES_PER_UPLOAD,
            ],
            'photos.*' => [
                'required',
                'file',
                'extensions:'.implode(',', MaintenanceAttachmentOptions::EXTENSIONS),
                'mimes:'.implode(',', MaintenanceAttachmentOptions::EXTENSIONS),
                'mimetypes:'.implode(',', MaintenanceAttachmentOptions::MIME_TYPES),
                'max:'.MaintenanceAttachmentOptions::MAX_FILE_KILOBYTES,
                'dimensions:max_width='.MaintenanceAttachmentOptions::MAX_DIMENSION
                    .',max_height='.MaintenanceAttachmentOptions::MAX_DIMENSION,
            ],
        ];
    }

    private function __construct() {}
}
