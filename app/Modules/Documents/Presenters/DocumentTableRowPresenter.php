<?php

namespace App\Modules\Documents\Presenters;

use App\Models\Document;
use App\Modules\Documents\Support\DocumentAttachments;
use Illuminate\Database\Eloquent\Model;

final class DocumentTableRowPresenter
{
    public function __construct(private readonly DocumentAttachments $attachments) {}

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
            'created_at' => $document->created_at?->toDateTimeString(),
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
