<?php

namespace App\Modules\Media\Actions;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Media\Support\MediaFileStorage;
use App\Modules\Media\Support\MediaUsage;
use Illuminate\Support\Facades\DB;

final class DeleteMediaFile
{
    public function __construct(
        private readonly MediaAccess $access,
        private readonly MediaUsage $usage,
        private readonly MediaFileStorage $files,
    ) {}

    public function handle(User $actor, MediaFile $mediaFile): void
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
        }, 3);

        $this->files->delete($disk, $path);
    }
}
