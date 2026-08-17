<?php

namespace App\Modules\Documents\Support;

use App\Models\Document;

final class DocumentReplacementLinks
{
    public function create(Document $document, string $attachmentAlias): string
    {
        return route('documents.create', [
            'documentable_type' => $attachmentAlias,
            'documentable_id' => $document->documentable_id,
            'type' => $document->type,
            'title_en' => $document->title_en,
            'title_ar' => $document->title_ar,
            'is_public' => $document->is_public ? 1 : 0,
        ]);
    }
}
