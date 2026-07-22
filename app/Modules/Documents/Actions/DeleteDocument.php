<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentFileStorage;
use Illuminate\Support\Facades\DB;

final class DeleteDocument
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentFileStorage $files,
    ) {}

    public function handle(User $actor, Document $document): void
    {
        $this->access->ensureCanManage($actor, $document);

        [$disk, $path] = DB::transaction(function () use ($actor, $document): array {
            $lockedDocument = Document::query()->lockForUpdate()->whereKey($document->id)->firstOrFail();
            $this->access->ensureCanManage($actor, $lockedDocument);
            $file = [(string) $lockedDocument->disk, (string) $lockedDocument->file_path];
            $lockedDocument->delete();

            return $file;
        }, 3);

        $this->files->delete($disk, $path);
    }
}
