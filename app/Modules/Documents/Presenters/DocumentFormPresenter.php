<?php

namespace App\Modules\Documents\Presenters;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;

class DocumentFormPresenter
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentAttachments $attachments,
        private readonly PortfolioScope $portfolios,
        private readonly ResourcePresenter $resources,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?Document $document = null, array $defaults = []): array
    {
        if ($document) {
            $this->access->ensureCanManage($actor, $document);

            return $this->editForm($document);
        }

        $this->access->ensureManager($actor);

        return $this->createForm($actor, $defaults);
    }

    /** @return array<string, mixed> */
    private function editForm(Document $document): array
    {
        $document->loadMissing('documentable');
        $attachment = $this->attachments->present($document->documentable);
        $fields = $this->resources->sectionFields([
            ['name' => 'type', 'label' => trans('app.documents.document_type'), 'type' => 'select', 'required' => true, 'options' => $this->resources->fieldOptions(DocumentOptions::TYPES)],
            ['name' => 'title_en', 'label' => trans('app.documents.english_title'), 'required' => true],
            ['name' => 'title_ar', 'label' => trans('app.documents.arabic_title'), 'required' => true],
            ['name' => 'is_public', 'label' => trans('app.documents.portal_checkbox'), 'type' => 'checkbox', 'help' => trans('app.documents.portal_internal_help')],
        ], [
            trans('app.documents.document_identity') => [
                'description' => trans('app.documents.edit_identity_help'),
                'fields' => ['type', 'title_en', 'title_ar', 'is_public'],
            ],
        ]);

        return [
            'title' => trans('app.documents.edit_document'),
            'description' => $this->attachmentDescription($attachment, edit: true),
            'backHref' => route('documents.show', $document),
            'backLabel' => trans('app.documents.detail_back'),
            'action' => route('documents.update', $document),
            'method' => 'put',
            'submitLabel' => trans('app.documents.update_document'),
            'fields' => $fields,
            'initialValues' => [
                'type' => $document->type,
                'title_en' => $document->title_en,
                'title_ar' => $document->title_ar ?? '',
                'is_public' => $document->is_public,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function createForm(User $actor, array $defaults): array
    {
        $attachmentAlias = in_array($defaults['documentable_type'] ?? null, DocumentOptions::ATTACHMENTS, true)
            ? (string) $defaults['documentable_type']
            : 'lease';
        $attachmentId = filter_var($defaults['documentable_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;
        $attachment = null;

        if ($attachmentId) {
            $record = $this->attachments->resolve($attachmentAlias, (int) $attachmentId);
            $this->portfolios->ensureAccess($actor, (int) $record->getAttribute('portfolio_id'));
            $attachment = $this->attachments->present($record);
        }

        $type = in_array($defaults['type'] ?? null, DocumentOptions::TYPES, true)
            ? (string) $defaults['type']
            : 'signed_contract';
        $targetFields = $attachment
            ? [
                ['name' => 'documentable_type', 'label' => trans('app.documents.attach_to'), 'type' => 'hidden'],
                ['name' => 'documentable_id', 'label' => trans('app.documents.attached_record_id'), 'type' => 'hidden'],
            ]
            : [
                ['name' => 'documentable_type', 'label' => trans('app.documents.attach_to'), 'type' => 'select', 'required' => true, 'options' => $this->resources->fieldOptions(DocumentOptions::ATTACHMENTS)],
                ['name' => 'documentable_id', 'label' => trans('app.documents.attached_record_id'), 'type' => 'number', 'required' => true, 'help' => trans('app.documents.attach_id_help')],
            ];
        $fields = $this->resources->sectionFields([
            ...$targetFields,
            ['name' => 'type', 'label' => trans('app.documents.document_type'), 'type' => 'select', 'required' => true, 'options' => $this->resources->fieldOptions(DocumentOptions::TYPES)],
            ['name' => 'title_en', 'label' => trans('app.documents.english_title'), 'required' => true],
            ['name' => 'title_ar', 'label' => trans('app.documents.arabic_title'), 'required' => true],
            ['name' => 'is_public', 'label' => trans('app.documents.portal_checkbox'), 'type' => 'checkbox', 'help' => trans('app.documents.portal_types_help')],
            ['name' => 'file', 'label' => trans('app.documents.pdf_file'), 'type' => 'file', 'required' => true, 'accept' => '.pdf,application/pdf', 'help' => trans('app.documents.pdf_help')],
        ], [
            trans('app.documents.attachment') => [
                'description' => trans('app.documents.attachment_help'),
                'fields' => ['documentable_type', 'documentable_id'],
            ],
            trans('app.documents.document_identity') => [
                'description' => trans('app.documents.identity_help'),
                'fields' => ['type', 'title_en', 'title_ar', 'is_public'],
            ],
            trans('app.documents.pdf_file') => [
                'description' => trans('app.documents.pdf_private_help'),
                'fields' => ['file'],
            ],
        ]);

        return [
            'title' => trans('app.documents.upload_document'),
            'description' => $this->attachmentDescription($attachment),
            'backHref' => route('documents.index'),
            'backLabel' => trans('app.documents.all_documents'),
            'action' => route('documents.store'),
            'method' => 'post',
            'submitLabel' => trans('app.documents.upload_document'),
            'fields' => $fields,
            'initialValues' => [
                'documentable_type' => $attachmentAlias,
                'documentable_id' => (string) ($attachmentId ?? ''),
                'type' => $type,
                'title_en' => (string) ($defaults['title_en'] ?? ''),
                'title_ar' => (string) ($defaults['title_ar'] ?? ''),
                'is_public' => DocumentOptions::canShowInPortal($attachmentAlias, $type),
                'file' => null,
            ],
        ];
    }

    /** @param array{type:string,label:string,url:string}|null $attachment */
    private function attachmentDescription(?array $attachment, bool $edit = false): string
    {
        if (! $attachment) {
            return trans('app.documents.attach_private_pdf');
        }

        return $edit
            ? trans('app.documents.edit_attachment', ['record' => $attachment['label']])
            : trans('app.documents.upload_attachment', ['record' => $attachment['label']]);
    }
}
