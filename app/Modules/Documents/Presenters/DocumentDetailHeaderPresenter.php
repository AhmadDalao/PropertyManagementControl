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
                ['label' => trans('app.documents.edit_document'), 'href' => route('documents.edit', $document), 'variant' => 'primary'],
                ['label' => trans('app.documents.download_pdf'), 'href' => route('documents.download', $document), 'variant' => 'secondary', 'external' => true],
            ],
        ];
    }
}
