<?php

namespace App\Modules\Media\Actions;

use App\Models\MediaFile;
use App\Models\User;

final class ManageMediaFiles
{
    public function __construct(
        private readonly CreateMediaFile $create,
        private readonly UpdateMediaFile $update,
        private readonly DeleteMediaFile $delete,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): MediaFile
    {
        return $this->create->handle($actor, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, MediaFile $mediaFile, array $data): MediaFile
    {
        return $this->update->handle($actor, $mediaFile, $data);
    }

    public function delete(User $actor, MediaFile $mediaFile): void
    {
        $this->delete->handle($actor, $mediaFile);
    }
}
