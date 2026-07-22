<?php

namespace App\Modules\Documents\Presenters;

use App\Modules\Documents\Data\DocumentFormData;

final class DocumentEditFormPresenter
{
    public function __construct(private readonly DocumentFormFieldsPresenter $fields) {}

    /** @return array<string, mixed> */
    public function present(DocumentFormData $data): array
    {
        $document = $data->document;
        assert($document !== null);

        return [
            'title' => trans('app.documents.edit_document'),
            'description' => $data->attachment
                ? trans('app.documents.edit_attachment', ['record' => $data->attachment['label']])
                : trans('app.documents.attach_private_pdf'),
            'backHref' => route('documents.show', $document),
            'backLabel' => trans('app.documents.detail_back'),
            'action' => route('documents.update', $document),
            'method' => 'put',
            'submitLabel' => trans('app.documents.update_document'),
            'fields' => $this->fields->edit(),
            'initialValues' => [
                'type' => $document->type,
                'title_en' => $document->title_en,
                'title_ar' => $document->title_ar ?? '',
                'is_public' => $document->is_public,
            ],
        ];
    }
}
