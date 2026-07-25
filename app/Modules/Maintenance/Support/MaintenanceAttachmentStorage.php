<?php

namespace App\Modules\Maintenance\Support;

use App\Models\MaintenanceAttachment;
use App\Modules\Maintenance\Data\StoredMaintenancePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MaintenanceAttachmentStorage
{
    public function store(
        UploadedFile $file,
        int $portfolioId,
        int $requestId,
    ): StoredMaintenancePhoto {
        [$mimeType, $width, $height] = $this->imageMetadata($file);
        $path = $file->store(
            MaintenanceAttachmentOptions::directory($portfolioId, $requestId),
            'local',
        );

        if ($path === false) {
            throw new RuntimeException(trans('app.errors.maintenance_photo_store_failed'));
        }

        return new StoredMaintenancePhoto(
            disk: 'local',
            path: $path,
            originalName: $this->safeFileName($file->getClientOriginalName()),
            mimeType: $mimeType,
            size: max(0, (int) $file->getSize()),
            width: $width,
            height: $height,
        );
    }

    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }

    public function response(MaintenanceAttachment $attachment): StreamedResponse
    {
        abort_unless(
            Storage::disk($attachment->disk)->exists($attachment->file_path),
            404,
            trans('app.errors.maintenance_photo_missing'),
        );

        return Storage::disk($attachment->disk)->response(
            $attachment->file_path,
            $this->safeFileName(
                $attachment->original_name,
                "maintenance-photo-{$attachment->id}.jpg",
            ),
            [
                'Content-Type' => $attachment->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    /** @return array{0:string,1:int,2:int} */
    private function imageMetadata(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        $dimensions = $realPath !== false ? @getimagesize($realPath) : false;

        if ($dimensions === false) {
            throw $this->invalidImage();
        }

        $mimeType = $dimensions['mime'];
        $width = (int) $dimensions[0];
        $height = (int) $dimensions[1];

        if (
            ! in_array($mimeType, MaintenanceAttachmentOptions::MIME_TYPES, true)
            || $width < 1
            || $height < 1
            || $width > MaintenanceAttachmentOptions::MAX_DIMENSION
            || $height > MaintenanceAttachmentOptions::MAX_DIMENSION
        ) {
            throw $this->invalidImage();
        }

        return [$mimeType, $width, $height];
    }

    private function invalidImage(): ValidationException
    {
        return ValidationException::withMessages([
            'photos' => trans('app.errors.maintenance_photo_invalid'),
        ]);
    }

    private function safeFileName(string $name, string $fallback = 'maintenance-photo.jpg'): string
    {
        $name = trim(str_replace(["\r", "\n"], '', basename(str_replace('\\', '/', $name))));

        return $name !== '' ? $name : $fallback;
    }
}
