<?php

namespace App\Modules\Documents\Presenters;

use App\Modules\Documents\Data\DocumentDetailData;
use App\Modules\Documents\Support\DocumentOptions;

final class DocumentDetailHeaderPresenter
{
    /** @return array<string, mixed> */
    public function present(DocumentDetailData $data): array
    {
        $document = $data->document;

        return [
            'eyebrow' => trans('app.documents.detail_eyebrow'),
            'title' => $data->title,
            'description' => DocumentOptions::label($document->type).' · '.$document->original_name,
            'backHref' => route('documents.index'),
            'backLabel' => trans('app.documents.all_documents'),
            'actions' => [
                ['label' => trans('app.documents.download_pdf'), 'href' => route('documents.download', $document), 'variant' => 'primary', 'external' => true],
                ['label' => trans('app.documents.edit_document'), 'href' => route('documents.edit', $document), 'variant' => 'secondary'],
                [
                    'label' => trans('app.documents.delete_document'),
                    'href' => route('documents.destroy', $document),
                    'method' => 'delete',
                    'variant' => 'danger',
                    'confirm' => trans('app.documents.delete_confirm', ['title' => $data->title]),
                ],
            ],
        ];
    }
}
