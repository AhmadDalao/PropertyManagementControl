<?php

namespace App\Modules\Media\Support;

use App\Models\MediaFile;
use App\Modules\Media\Data\MediaRelocation;
use App\Modules\Media\Data\StoredMediaImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class MediaFileStorage
{
    public function store(UploadedFile $file, ?int $portfolioId, string $visibility): StoredMediaImage
    {
        [$mimeType, $width, $height] = $this->imageMetadata($file);
        $disk = MediaOptions::diskFor($visibility);
        $path = $file->store(MediaOptions::directoryFor($portfolioId), $disk);

        if ($path === false) {
            throw new RuntimeException(trans('app.errors.media_store_failed'));
        }

        return new StoredMediaImage(
            disk: $disk,
            path: $path,
            mimeType: $mimeType,
            size: max(0, (int) $file->getSize()),
            width: $width,
            height: $height,
        );
    }

    public function prepareRelocation(
        MediaFile $mediaFile,
        ?int $targetPortfolioId,
        string $visibility,
    ): ?MediaRelocation {
        $targetDisk = MediaOptions::diskFor($visibility);
        $targetPath = $mediaFile->portfolio_id === $targetPortfolioId
            ? (string) $mediaFile->path
            : MediaOptions::directoryFor($targetPortfolioId).'/'.basename((string) $mediaFile->path);

        if ($targetDisk === $mediaFile->disk && $targetPath === $mediaFile->path) {
            return null;
        }

        $targetPath = $this->availablePath($targetDisk, $targetPath);
        $relocation = new MediaRelocation(
            sourceDisk: (string) $mediaFile->disk,
            sourcePath: (string) $mediaFile->path,
            targetDisk: $targetDisk,
            targetPath: $targetPath,
        );
        $this->copy($relocation);

        return $relocation;
    }

    public function discardTarget(MediaRelocation $relocation): void
    {
        Storage::disk($relocation->targetDisk)->delete($relocation->targetPath);
    }

    public function removeSource(MediaRelocation $relocation): void
    {
        $source = Storage::disk($relocation->sourceDisk);

        if ($source->delete($relocation->sourcePath) || ! $source->exists($relocation->sourcePath)) {
            return;
        }

        Log::warning('A relocated media source could not be removed.', [
            'disk' => $relocation->sourceDisk,
            'path' => $relocation->sourcePath,
        ]);
    }

    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }

    /** @return array{0:string,1:int,2:int} */
    private function imageMetadata(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        $dimensions = $realPath !== false ? @getimagesize($realPath) : false;

        if ($dimensions === false) {
            throw ValidationException::withMessages([
                'file' => trans('app.errors.media_invalid_image'),
            ]);
        }

        $mimeType = $dimensions['mime'];

        if (! in_array($mimeType, MediaOptions::MIME_TYPES, true)
            || (int) $dimensions[0] < 1
            || (int) $dimensions[1] < 1
            || (int) $dimensions[0] > MediaOptions::MAX_DIMENSION
            || (int) $dimensions[1] > MediaOptions::MAX_DIMENSION) {
            throw ValidationException::withMessages([
                'file' => trans('app.errors.media_invalid_image'),
            ]);
        }

        return [$mimeType, (int) $dimensions[0], (int) $dimensions[1]];
    }

    private function availablePath(string $disk, string $path): string
    {
        if (! Storage::disk($disk)->exists($path)) {
            return $path;
        }

        $directory = trim(dirname($path), '.');
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = (string) Str::uuid().($extension === '' ? '' : ".{$extension}");

        return ($directory === '' ? '' : $directory.'/').$filename;
    }

    private function copy(MediaRelocation $relocation): void
    {
        $source = Storage::disk($relocation->sourceDisk);
        $target = Storage::disk($relocation->targetDisk);
        $stream = $source->readStream($relocation->sourcePath);

        if (! is_resource($stream)) {
            throw new RuntimeException(trans('app.errors.media_file_missing'));
        }

        try {
            if (! $target->put($relocation->targetPath, $stream)) {
                throw new RuntimeException(trans('app.errors.media_move_failed'));
            }
        } catch (\Throwable $exception) {
            $target->delete($relocation->targetPath);

            throw $exception;
        } finally {
            fclose($stream);
        }
    }
}
