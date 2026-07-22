<?php

namespace App\Modules\Documents\Presenters;

use App\Modules\Documents\Data\DocumentFormData;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Shared\ResourcePresenter;

final class DocumentFormFieldsPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<int, array<string, mixed>> */
    public function create(DocumentFormData $data): array
    {
        $targetFields = $data->attachment
            ? [
                ['name' => 'documentable_type', 'label' => trans('app.documents.attach_to'), 'type' => 'hidden'],
                ['name' => 'documentable_id', 'label' => trans('app.documents.attached_record_id'), 'type' => 'hidden'],
            ]
            : [
                ['name' => 'documentable_type', 'label' => trans('app.documents.attach_to'), 'type' => 'select', 'required' => true, 'options' => $this->options(DocumentOptions::ATTACHMENTS)],
                ['name' => 'documentable_id', 'label' => trans('app.documents.attached_record_id'), 'type' => 'number', 'required' => true, 'min' => 1, 'help' => trans('app.documents.attach_id_help')],
            ];

        return $this->resources->sectionFields([
            ...$targetFields,
            ...$this->metadata(),
            ['name' => 'file', 'label' => trans('app.documents.pdf_file'), 'type' => 'file', 'required' => true, 'accept' => '.pdf,application/pdf', 'help' => trans('app.documents.pdf_help')],
        ], [
            trans('app.documents.attachment') => ['description' => trans('app.documents.attachment_help'), 'fields' => ['documentable_type', 'documentable_id']],
            trans('app.documents.document_identity') => ['description' => trans('app.documents.identity_help'), 'fields' => ['type', 'title_en', 'title_ar', 'is_public']],
            trans('app.documents.pdf_file') => ['description' => trans('app.documents.pdf_private_help'), 'fields' => ['file']],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function edit(): array
    {
        return $this->resources->sectionFields($this->metadata(), [
            trans('app.documents.document_identity') => [
                'description' => trans('app.documents.edit_identity_help'),
                'fields' => ['type', 'title_en', 'title_ar', 'is_public'],
            ],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function metadata(): array
    {
        return [
            ['name' => 'type', 'label' => trans('app.documents.document_type'), 'type' => 'select', 'required' => true, 'options' => $this->options(DocumentOptions::TYPES)],
            ['name' => 'title_en', 'label' => trans('app.documents.english_title'), 'required' => true, 'max' => 255],
            ['name' => 'title_ar', 'label' => trans('app.documents.arabic_title'), 'required' => true, 'max' => 255],
            ['name' => 'is_public', 'label' => trans('app.documents.portal_checkbox'), 'type' => 'checkbox', 'help' => trans('app.documents.portal_internal_help')],
        ];
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, array{value:string,label:string}>
     */
    private function options(array $values): array
    {
        return collect($values)->map(fn (string $value): array => [
            'value' => $value,
            'label' => DocumentOptions::label($value),
        ])->all();
    }
}
