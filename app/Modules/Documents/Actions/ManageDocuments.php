<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ManageDocuments
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentAttachments $attachments,
        private readonly PortfolioScope $portfolios,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): Document
    {
        $this->access->ensureManager($actor);
        $attachmentAlias = (string) $data['documentable_type'];
        $attachment = $this->attachments->resolve($attachmentAlias, (int) $data['documentable_id']);
        $portfolioId = (int) $attachment->getAttribute('portfolio_id');
        $this->portfolios->ensureAccess($actor, $portfolioId);

        $file = $data['file'] ?? null;

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages(['file' => trans('validation.required')]);
        }

        $path = $file->store("documents/library/{$portfolioId}", 'local');

        if ($path === false) {
            throw new RuntimeException('The PDF document could not be stored.');
        }

        try {
            return DB::transaction(function () use (
                $actor,
                $attachment,
                $attachmentAlias,
                $portfolioId,
                $file,
                $path,
                $data,
            ): Document {
                return Document::query()->create([
                    'portfolio_id' => $portfolioId,
                    'uploaded_by_user_id' => $actor->id,
                    'documentable_type' => $attachment->getMorphClass(),
                    'documentable_id' => $attachment->getKey(),
                    'type' => $data['type'],
                    'title_en' => $data['title_en'],
                    'title_ar' => $data['title_ar'],
                    'disk' => 'local',
                    'file_path' => $path,
                    'original_name' => $this->safeFileName($file->getClientOriginalName()),
                    'mime_type' => 'application/pdf',
                    'file_size' => $file->getSize(),
                    'is_public' => $this->portalVisible($attachmentAlias, $data),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, Document $document, array $data): Document
    {
        $this->access->ensureCanManage($actor, $document);

        return DB::transaction(function () use ($actor, $document, $data): Document {
            $lockedDocument = Document::query()->lockForUpdate()->whereKey($document->id)->firstOrFail();
            $this->access->ensureCanManage($actor, $lockedDocument);
            $attachmentAlias = $this->attachments->aliasForDocument($lockedDocument);

            if ($attachmentAlias === null) {
                throw ValidationException::withMessages([
                    'type' => trans('app.errors.unsupported_document_attachment'),
                ]);
            }

            $lockedDocument->update([
                'type' => $data['type'],
                'title_en' => $data['title_en'],
                'title_ar' => $data['title_ar'],
                'is_public' => $this->portalVisible($attachmentAlias, $data),
            ]);

            return $lockedDocument->fresh(['documentable']);
        });
    }

    public function delete(User $actor, Document $document): void
    {
        $this->access->ensureCanManage($actor, $document);

        [$disk, $path] = DB::transaction(function () use ($actor, $document): array {
            $lockedDocument = Document::query()->lockForUpdate()->whereKey($document->id)->firstOrFail();
            $this->access->ensureCanManage($actor, $lockedDocument);
            $file = [(string) $lockedDocument->disk, (string) $lockedDocument->file_path];
            $lockedDocument->delete();

            return $file;
        });

        Storage::disk($disk)->delete($path);
    }

    /** @param array<string, mixed> $data */
    private function portalVisible(string $attachmentAlias, array $data): bool
    {
        return (bool) ($data['is_public'] ?? false)
            && DocumentOptions::canShowInPortal($attachmentAlias, (string) $data['type']);
    }

    private function safeFileName(string $name): string
    {
        $name = trim(str_replace(["\r", "\n"], '', basename(str_replace('\\', '/', $name))));

        return $name !== '' ? $name : 'document.pdf';
    }
}
