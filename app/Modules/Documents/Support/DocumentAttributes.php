<?php

namespace App\Modules\Documents\Support;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Documents\Data\StoredDocumentFile;

final class DocumentAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forCreate(
        User $actor,
        Asset|Lease|Payment $attachment,
        string $attachmentAlias,
        StoredDocumentFile $file,
        array $data,
    ): array {
        return [
            'portfolio_id' => (int) $attachment->getAttribute('portfolio_id'),
            'uploaded_by_user_id' => $actor->id,
            'documentable_type' => $attachment->getMorphClass(),
            'documentable_id' => $attachment->getKey(),
            'type' => $data['type'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'disk' => $file->disk,
            'file_path' => $file->path,
            'original_name' => $file->originalName,
            'mime_type' => $file->mimeType,
            'file_size' => $file->size,
            'is_public' => $this->portalVisible($attachmentAlias, $data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forUpdate(string $attachmentAlias, array $data): array
    {
        return [
            'type' => $data['type'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'is_public' => $this->portalVisible($attachmentAlias, $data),
        ];
    }

    /** @param array<string, mixed> $data */
    private function portalVisible(string $attachmentAlias, array $data): bool
    {
        return (bool) ($data['is_public'] ?? false)
            && DocumentOptions::canShowInPortal($attachmentAlias, (string) $data['type']);
    }
}
