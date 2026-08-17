<?php

namespace App\Modules\Documents\Presenters;

use App\Modules\Documents\Data\DocumentDetailData;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

final class DocumentDetailOverviewPresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $userAccess,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function stats(DocumentDetailData $data): array
    {
        $document = $data->document;

        return $this->resources->detailItems([
            ['label' => trans('app.documents.type'), 'value' => DocumentOptions::label($document->type), 'tone' => 'primary'],
            ['label' => trans('app.documents.validity'), 'value' => DocumentOptions::label($data->expiryCode), 'tone' => in_array($data->expiryCode, ['expired', 'due_30'], true) ? 'danger' : 'teal'],
            ['label' => trans('app.documents.access'), 'value' => $document->is_public ? trans('app.documents.portal_visible') : trans('app.documents.internal')],
            ['label' => trans('app.documents.size'), 'value' => $this->fileSize((int) $document->file_size)],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(DocumentDetailData $data): array
    {
        $document = $data->document;

        return [[
            'key' => 'identity',
            'title' => trans('app.documents.identity_section'),
            'description' => trans('app.documents.identity_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.documents.english_title'), 'value' => $document->title_en],
                ['label' => trans('app.documents.arabic_title'), 'value' => $document->title_ar],
                ['label' => trans('app.documents.original_name'), 'value' => $document->original_name],
                ['label' => trans('app.documents.document_type'), 'value' => DocumentOptions::label($document->type)],
                ['label' => trans('app.documents.format'), 'value' => 'PDF'],
                ['label' => trans('app.documents.size'), 'value' => $this->fileSize((int) $document->file_size)],
                ['label' => trans('app.documents.uploaded_at'), 'value' => $document->created_at?->toDateTimeString()],
            ]),
        ], [
            'key' => 'ownership',
            'title' => trans('app.documents.ownership_section'),
            'description' => trans('app.documents.ownership_section_help'),
            'items' => $this->resources->detailItems([
                ['label' => trans('app.documents.attached_to'), 'value' => $data->attachment['label'] ?? '#'.$document->documentable_id, 'href' => $data->attachment['url'] ?? null],
                ['label' => trans('app.documents.attachment_type'), 'value' => DocumentOptions::label($data->attachmentAlias)],
                ['label' => trans('app.documents.portfolio'), 'value' => $this->resources->localized($document->portfolio?->name_en, $document->portfolio?->name_ar), 'href' => $document->portfolio ? route('portfolios.show', $document->portfolio) : null],
                ['label' => trans('app.documents.uploader'), 'value' => $document->uploadedBy?->name, 'href' => $this->userAccess->recordHref($data->actor, $document->uploadedBy)],
            ]),
        ]];
    }

    private function fileSize(int $bytes): string
    {
        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1).' MB'
            : number_format($bytes / 1024, 1).' KB';
    }
}
