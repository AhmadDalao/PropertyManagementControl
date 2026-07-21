<?php

namespace App\Modules\Media\Actions;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Media\Support\MediaOptions;
use App\Modules\Media\Support\MediaUsage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ManageMediaFiles
{
    public function __construct(
        private readonly MediaAccess $access,
        private readonly MediaUsage $usage,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): MediaFile
    {
        $this->access->ensureManager($actor);
        $portfolioId = array_key_exists('portfolio_id', $data)
            ? $this->nullableId($data['portfolio_id'])
            : $actor->portfolio_id;
        $this->access->ensurePortfolio($actor, $portfolioId);
        $file = $data['file'] ?? null;

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages(['file' => trans('validation.required')]);
        }

        [$mimeType, $width, $height] = $this->imageMetadata($file);
        $visibility = (string) $data['visibility'];
        $disk = MediaOptions::diskFor($visibility);
        $scope = $portfolioId === null ? 'website' : 'portfolios/'.$portfolioId;
        $path = $file->store('media/'.$scope, $disk);

        if ($path === false) {
            throw new RuntimeException(trans('app.errors.media_store_failed'));
        }

        try {
            return DB::transaction(fn (): MediaFile => MediaFile::query()->create([
                'uploaded_by_user_id' => $actor->id,
                'portfolio_id' => $portfolioId,
                'collection' => $data['collection'],
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'title_en' => $data['title_en'],
                'title_ar' => $data['title_ar'],
                'alt_text_en' => $data['alt_text_en'],
                'alt_text_ar' => $data['alt_text_ar'],
                'visibility' => $visibility,
            ]));
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, MediaFile $mediaFile, array $data): MediaFile
    {
        $this->access->ensureCanManage($actor, $mediaFile);
        $moved = null;

        try {
            $updated = DB::transaction(function () use ($actor, $mediaFile, $data, &$moved): MediaFile {
                $locked = MediaFile::query()->lockForUpdate()->whereKey($mediaFile->id)->firstOrFail();
                $this->access->ensureCanManage($actor, $locked);
                $portfolioId = array_key_exists('portfolio_id', $data)
                    ? $this->nullableId($data['portfolio_id'])
                    : $locked->portfolio_id;
                $this->access->ensurePortfolio($actor, $portfolioId);
                $visibility = (string) $data['visibility'];

                if ($visibility !== 'public') {
                    $this->usage->ensureUnused($locked);
                }

                $targetDisk = MediaOptions::diskFor($visibility);

                if ($targetDisk !== $locked->disk) {
                    $moved = [
                        'from' => (string) $locked->disk,
                        'to' => $targetDisk,
                        'path' => (string) $locked->path,
                    ];
                    $this->moveFile($moved['from'], $moved['to'], $moved['path']);
                }

                $locked->update([
                    'portfolio_id' => $portfolioId,
                    'collection' => $data['collection'],
                    'disk' => $targetDisk,
                    'title_en' => $data['title_en'],
                    'title_ar' => $data['title_ar'],
                    'alt_text_en' => $data['alt_text_en'],
                    'alt_text_ar' => $data['alt_text_ar'],
                    'visibility' => $visibility,
                ]);

                return $locked->fresh(['portfolio', 'uploadedBy']);
            });
        } catch (Throwable $exception) {
            if (is_array($moved)) {
                $this->restoreFile($moved['from'], $moved['to'], $moved['path']);
            }

            throw $exception;
        }

        return $updated;
    }

    public function delete(User $actor, MediaFile $mediaFile): void
    {
        $this->access->ensureCanManage($actor, $mediaFile);
        $this->usage->ensureUnused($mediaFile);

        [$disk, $path] = DB::transaction(function () use ($actor, $mediaFile): array {
            $locked = MediaFile::query()->lockForUpdate()->whereKey($mediaFile->id)->firstOrFail();
            $this->access->ensureCanManage($actor, $locked);
            $this->usage->ensureUnused($locked);
            $storedFile = [(string) $locked->disk, (string) $locked->path];
            $locked->delete();

            return $storedFile;
        });

        Storage::disk($disk)->delete($path);
    }

    /** @return array{0:string,1:int,2:int} */
    private function imageMetadata(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType();
        $realPath = $file->getRealPath();
        $dimensions = $realPath !== false ? @getimagesize($realPath) : false;

        if (! is_string($mimeType) || ! in_array($mimeType, MediaOptions::MIME_TYPES, true) || $dimensions === false) {
            throw ValidationException::withMessages([
                'file' => trans('app.errors.media_invalid_image'),
            ]);
        }

        return [$mimeType, (int) $dimensions[0], (int) $dimensions[1]];
    }

    private function moveFile(string $from, string $to, string $path): void
    {
        $source = Storage::disk($from);
        $target = Storage::disk($to);
        $stream = $source->readStream($path);

        if (! is_resource($stream)) {
            throw new RuntimeException(trans('app.errors.media_file_missing'));
        }

        try {
            if (! $target->put($path, $stream)) {
                throw new RuntimeException(trans('app.errors.media_move_failed'));
            }
        } finally {
            fclose($stream);
        }

        if (! $source->delete($path) && $source->exists($path)) {
            $target->delete($path);

            throw new RuntimeException(trans('app.errors.media_move_failed'));
        }
    }

    private function restoreFile(string $originalDisk, string $currentDisk, string $path): void
    {
        try {
            if (Storage::disk($currentDisk)->exists($path)) {
                $this->moveFile($currentDisk, $originalDisk, $path);
            }
        } catch (Throwable) {
            // Preserve the original exception; deployment monitoring will surface a failed rollback copy.
        }
    }

    private function nullableId(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
