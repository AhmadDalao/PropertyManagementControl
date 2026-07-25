<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Data\StoredMaintenancePhoto;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceAttachmentOptions;
use App\Modules\Maintenance\Support\MaintenanceAttachmentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Throwable;

final class PersistMaintenanceAttachments
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly MaintenanceAttachmentStorage $storage,
    ) {}

    /**
     * The caller owns the database transaction.
     *
     * @param  array<int, mixed>  $files
     * @return Collection<int, MaintenanceAttachment>
     */
    public function handle(User $actor, MaintenanceRequest $request, array $files): Collection
    {
        $this->access->ensureCanAccess($actor, $request);

        if ($files === []) {
            return collect();
        }

        $lockedRequest = MaintenanceRequest::query()
            ->whereKey($request->id)
            ->lockForUpdate()
            ->firstOrFail();
        $existingCount = $lockedRequest->attachments()->count();

        if ($existingCount + count($files) > MaintenanceAttachmentOptions::MAX_FILES_PER_REQUEST) {
            throw ValidationException::withMessages([
                'photos' => trans('app.errors.maintenance_photo_limit', [
                    'count' => MaintenanceAttachmentOptions::MAX_FILES_PER_REQUEST,
                ]),
            ]);
        }

        $stored = [];
        $attachments = collect();

        try {
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    throw ValidationException::withMessages([
                        'photos' => trans('app.errors.maintenance_photo_invalid'),
                    ]);
                }

                $photo = $this->storage->store(
                    $file,
                    (int) $lockedRequest->portfolio_id,
                    (int) $lockedRequest->id,
                );
                $stored[] = $photo;
                $attachments->push($lockedRequest->attachments()->create([
                    'portfolio_id' => $lockedRequest->portfolio_id,
                    'uploaded_by_user_id' => $actor->id,
                    'disk' => $photo->disk,
                    'file_path' => $photo->path,
                    'original_name' => $photo->originalName,
                    'mime_type' => $photo->mimeType,
                    'file_size' => $photo->size,
                    'width' => $photo->width,
                    'height' => $photo->height,
                ]));
            }
        } catch (Throwable $exception) {
            $this->discardStored($stored);

            throw $exception;
        }

        return $attachments;
    }

    /** @param iterable<int, MaintenanceAttachment> $attachments */
    public function discard(iterable $attachments): void
    {
        foreach ($attachments as $attachment) {
            $this->storage->delete($attachment->disk, $attachment->file_path);
        }
    }

    /** @param array<int, StoredMaintenancePhoto> $stored */
    private function discardStored(array $stored): void
    {
        foreach ($stored as $photo) {
            $this->storage->delete($photo->disk, $photo->path);
        }
    }
}
