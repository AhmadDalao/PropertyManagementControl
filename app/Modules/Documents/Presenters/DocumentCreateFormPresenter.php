<?php

namespace App\Modules\Documents\Presenters;

use App\Modules\Documents\Data\DocumentFormData;
use App\Modules\Documents\Support\DocumentOptions;

final class DocumentCreateFormPresenter
{
    public function __construct(private readonly DocumentFormFieldsPresenter $fields) {}

    /** @return array<string, mixed> */
    public function present(DocumentFormData $data): array
    {
        return [
            'title' => trans('app.documents.upload_document'),
            'description' => $data->attachment
                ? trans('app.documents.upload_attachment', ['record' => $data->attachment['label']])
                : trans('app.documents.attach_private_pdf'),
            'backHref' => route('documents.index'),
            'backLabel' => trans('app.documents.all_documents'),
            'action' => route('documents.store'),
            'method' => 'post',
            'submitLabel' => trans('app.documents.upload_document'),
            'fields' => $this->fields->create($data),
            'initialValues' => [
                'documentable_type' => $data->attachmentAlias,
                'documentable_id' => (string) ($data->attachmentId ?? ''),
                'type' => $data->type,
                'title_en' => (string) ($data->defaults['title_en'] ?? ''),
                'title_ar' => (string) ($data->defaults['title_ar'] ?? ''),
                'is_public' => DocumentOptions::canShowInPortal($data->attachmentAlias, $data->type),
                'file' => null,
            ],
        ];
    }
}
