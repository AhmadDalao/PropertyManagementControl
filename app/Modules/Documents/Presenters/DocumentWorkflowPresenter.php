<?php

namespace App\Modules\Documents\Presenters;

use App\Modules\Documents\Data\DocumentDetailData;
use App\Modules\Documents\Support\DocumentOptions;

final class DocumentWorkflowPresenter
{
    /** @return array<string, mixed> */
    public function present(DocumentDetailData $data): array
    {
        $needsReplacement = in_array($data->expiryCode, ['expired', 'due_30', 'due_90'], true);
        $actions = [];

        if ($needsReplacement && $data->replacementUrl) {
            $actions[] = [
                'label' => trans('app.documents.upload_replacement'),
                'href' => $data->replacementUrl,
                'variant' => 'primary',
            ];
        }

        if ($data->attachment) {
            $actions[] = [
                'label' => trans('app.documents.open_linked_record'),
                'href' => $data->attachment['url'],
                'variant' => 'secondary',
            ];
        }

        return [
            'eyebrow' => trans('app.resource.next_step'),
            'title' => trans("app.documents.workflow_{$data->expiryCode}_title"),
            'description' => trans("app.documents.workflow_{$data->expiryCode}_description"),
            'status' => DocumentOptions::label($data->expiryCode),
            'tone' => match ($data->expiryCode) {
                'expired', 'due_30' => 'danger',
                'due_90' => 'primary',
                'current' => 'teal',
                default => 'muted',
            },
            'icon' => $needsReplacement ? 'bi-calendar2-x' : 'bi-file-earmark-check',
            'actions' => $actions,
        ];
    }
}
