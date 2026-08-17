<?php

namespace App\Modules\Documents\Queries;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Data\DocumentDetailData;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentExpiryState;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Documents\Support\DocumentReplacementLinks;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Database\Eloquent\Model;

final class DocumentDetailQuery
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentAttachments $attachments,
        private readonly DocumentExpiryState $expiry,
        private readonly DocumentReplacementLinks $replacement,
        private readonly ResourcePresenter $resources,
    ) {}

    public function get(Document $document, User $actor): DocumentDetailData
    {
        $this->access->ensureCanManage($actor, $document);
        $document->loadMissing(['portfolio', 'uploadedBy', 'documentable']);
        $alias = $this->attachments->aliasForDocument($document);
        $canReplace = $alias !== null
            && ! DocumentOptions::isPaymentProof($document->type)
            && $document->portfolio?->status === 'active';

        return new DocumentDetailData(
            document: $document,
            actor: $actor,
            title: $this->resources->localized($document->title_en, $document->title_ar)
                ?: $document->original_name,
            attachment: $this->attachments->present(
                $document->documentable instanceof Model ? $document->documentable : null,
            ),
            attachmentAlias: $alias ?? 'other',
            expiryCode: $this->expiry->code($document->expires_on),
            expiryDays: $this->expiry->daysRemaining($document->expires_on),
            portalEligible: $alias !== null
                && DocumentOptions::canShowInPortal($alias, $document->type),
            replacementUrl: $canReplace
                ? $this->replacement->create($document, $alias)
                : null,
        );
    }
}
