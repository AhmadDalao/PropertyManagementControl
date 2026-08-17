<?php

namespace App\Modules\Documents\Presenters;

use App\Modules\Documents\Data\DocumentDetailData;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Shared\ResourcePresenter;

final class DocumentValidityPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array{section:array<string,mixed>,replacement:array<string,mixed>} */
    public function present(DocumentDetailData $data): array
    {
        $document = $data->document;

        return [
            'section' => [
                'key' => 'validity',
                'title' => trans('app.documents.validity_section'),
                'description' => trans('app.documents.validity_section_help'),
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.documents.issued_on'), 'value' => $document->issued_on?->toDateString()],
                    ['label' => trans('app.documents.expires_on'), 'value' => $document->expires_on?->toDateString() ?? trans('app.documents.expiry_no_expiry')],
                    ['label' => trans('app.documents.expiry_status'), 'value' => DocumentOptions::label($data->expiryCode)],
                    ['label' => trans('app.documents.validity_window'), 'value' => $this->days($data)],
                    ['label' => trans('app.documents.replacement_policy'), 'value' => trans('app.documents.replacement_creates_record')],
                ]),
            ],
            'replacement' => [
                'can_upload' => $data->replacementUrl !== null,
                'upload_url' => $data->replacementUrl,
                'title' => trans('app.documents.replacement_title'),
                'description' => trans('app.documents.replacement_help'),
                'action_label' => trans('app.documents.upload_replacement'),
                'unavailable' => trans('app.documents.replacement_unavailable'),
            ],
        ];
    }

    private function days(DocumentDetailData $data): string
    {
        if ($data->expiryDays === null) {
            return trans('app.documents.expiry_no_expiry');
        }

        return $data->expiryDays < 0
            ? trans('app.documents.days_expired', ['count' => abs($data->expiryDays)])
            : trans('app.documents.days_remaining', ['count' => $data->expiryDays]);
    }
}
