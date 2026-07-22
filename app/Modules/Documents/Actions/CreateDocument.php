<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachmentResolver;
use App\Modules\Documents\Support\DocumentAttributes;
use App\Modules\Documents\Support\DocumentFileStorage;
use App\Modules\Documents\Support\DocumentInputGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CreateDocument
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentAttachmentResolver $attachments,
        private readonly DocumentAttributes $attributes,
        private readonly DocumentFileStorage $files,
        private readonly DocumentInputGuard $input,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): Document
    {
        $this->access->ensureManager($actor);
        $data = $this->input->validateCreate($data);
        $alias = (string) $data['documentable_type'];
        $attachment = $this->attachments->resolve($actor, $alias, (int) $data['documentable_id']);
        $file = $data['file'];
        assert($file instanceof UploadedFile);
        $storedFile = $this->files->store($file, (int) $attachment->getAttribute('portfolio_id'));

        try {
            return DB::transaction(function () use ($actor, $alias, $data, $storedFile): Document {
                $attachment = $this->attachments->resolve(
                    $actor,
                    $alias,
                    (int) $data['documentable_id'],
                    lock: true,
                );

                return Document::query()->create(
                    $this->attributes->forCreate($actor, $attachment, $alias, $storedFile, $data),
                );
            }, 3);
        } catch (Throwable $exception) {
            $this->files->delete($storedFile->disk, $storedFile->path);

            throw $exception;
        }
    }
}
