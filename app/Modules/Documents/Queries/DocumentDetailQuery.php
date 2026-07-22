<?php

namespace App\Modules\Documents\Queries;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Data\DocumentDetailData;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Database\Eloquent\Model;

final class DocumentDetailQuery
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentAttachments $attachments,
        private readonly ResourcePresenter $resources,
    ) {}

    public function get(Document $document, User $actor): DocumentDetailData
    {
        $this->access->ensureCanManage($actor, $document);
        $document->loadMissing(['portfolio', 'uploadedBy', 'documentable']);

        return new DocumentDetailData(
            document: $document,
            actor: $actor,
            title: $this->resources->localized($document->title_en, $document->title_ar)
                ?: $document->original_name,
            attachment: $this->attachments->present(
                $document->documentable instanceof Model ? $document->documentable : null,
            ),
        );
    }
}
