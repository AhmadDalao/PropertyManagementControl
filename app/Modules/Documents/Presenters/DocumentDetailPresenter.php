<?php

namespace App\Modules\Documents\Presenters;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Database\Eloquent\Model;

class DocumentDetailPresenter
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentAttachments $attachments,
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $userAccess,
    ) {}

    /** @return array<string, mixed> */
    public function present(Document $document, User $actor): array
    {
        $this->access->ensureCanManage($actor, $document);
        $document->loadMissing(['portfolio', 'uploadedBy', 'documentable']);
        $localizedTitle = $this->resources->localized($document->title_en, $document->title_ar)
            ?: $document->original_name;
        $attachment = $this->attachments->present(
            $document->documentable instanceof Model ? $document->documentable : null,
        );

        return [
            'header' => [
                'eyebrow' => trans('app.documents.detail_eyebrow'),
                'title' => $localizedTitle,
                'description' => trim($document->type.' · '.$document->original_name),
                'backHref' => route('documents.index'),
                'backLabel' => trans('app.documents.all_documents'),
                'actions' => [
                    ['label' => trans('app.documents.edit_document'), 'href' => route('documents.edit', $document), 'variant' => 'primary'],
                    ['label' => trans('app.documents.download_pdf'), 'href' => route('documents.download', $document), 'variant' => 'secondary'],
                ],
            ],
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.documents.type'), 'value' => $document->type, 'tone' => 'primary'],
                ['label' => trans('app.documents.tenant_portal'), 'value' => $document->is_public ? trans('app.documents.visible') : trans('app.documents.internal')],
                ['label' => trans('app.documents.size'), 'value' => number_format((float) $document->file_size / 1024, 1).' KB'],
                ['label' => trans('app.documents.format'), 'value' => 'PDF'],
            ]),
            'sections' => [[
                'title' => trans('app.documents.file_record'),
                'description' => trans('app.documents.file_record_description'),
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.documents.english_title'), 'value' => $document->title_en],
                    ['label' => trans('app.documents.arabic_title'), 'value' => $document->title_ar],
                    ['label' => trans('app.documents.original_name'), 'value' => $document->original_name],
                    ['label' => trans('app.documents.attached_to'), 'value' => $attachment['label'] ?? '#'.$document->documentable_id, 'href' => $attachment['url'] ?? null],
                    ['label' => trans('app.documents.attachment_type'), 'value' => $attachment['type'] ?? $this->attachments->aliasForDocument($document)],
                    ['label' => trans('app.documents.portfolio'), 'value' => $this->resources->localized($document->portfolio?->name_en, $document->portfolio?->name_ar), 'href' => $document->portfolio ? route('portfolios.show', $document->portfolio) : null],
                    ['label' => trans('app.documents.uploader'), 'value' => $document->uploadedBy?->name, 'href' => $this->userAccess->recordHref($actor, $document->uploadedBy)],
                    ['label' => trans('app.documents.uploaded_at'), 'value' => $document->created_at?->toDateTimeString()],
                ]),
            ]],
            'related' => [],
            'documents' => $this->resources->documentStrip([$document]),
            'timeline' => $this->resources->activityTimeline($document),
        ];
    }
}
