<?php

namespace App\Modules\Documents\Presenters;

use App\Models\Document;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentExpiryState;
use Illuminate\Database\Eloquent\Model;

final class DocumentTableRowPresenter
{
    public function __construct(
        private readonly DocumentAttachments $attachments,
        private readonly DocumentExpiryState $expiry,
    ) {}

    /** @return array<string, mixed> */
    public function present(Document $document): array
    {
        $document->loadMissing(['portfolio', 'uploadedBy', 'documentable']);
        $attachment = $this->attachments->present(
            $document->documentable instanceof Model ? $document->documentable : null,
        );

        return [
            'id' => $document->id,
            'type' => $document->type,
            'title_en' => $document->title_en,
            'title_ar' => $document->title_ar,
            'original_name' => $document->original_name,
            'file_size' => $document->file_size,
            'is_public' => $document->is_public,
            'issued_on' => $document->issued_on?->toDateString(),
            'expires_on' => $document->expires_on?->toDateString(),
            'expiry_status' => $this->expiry->code($document->expires_on),
            'expiry_days' => $this->expiry->daysRemaining($document->expires_on),
            'created_at' => $document->created_at?->toDateTimeString(),
            'review_status' => $document->type === 'payment_proof'
                ? data_get($document->meta_json, 'review_status', 'pending')
                : null,
            'download_url' => route('documents.download', $document),
            'attachment' => $attachment ?? [
                'type' => $this->attachments->aliasForDocument($document) ?? 'record',
                'label' => '#'.$document->documentable_id,
                'url' => null,
            ],
            'portfolio' => [
                'name_en' => $document->portfolio?->name_en,
                'name_ar' => $document->portfolio?->name_ar,
            ],
            'uploaded_by' => ['name' => $document->uploadedBy?->name],
        ];
    }
}
