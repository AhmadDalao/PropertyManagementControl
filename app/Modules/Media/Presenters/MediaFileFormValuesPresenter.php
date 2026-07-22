<?php

namespace App\Modules\Media\Presenters;

use App\Modules\Media\Data\MediaFileFormData;

final class MediaFileFormValuesPresenter
{
    /** @return array<string, mixed> */
    public function present(MediaFileFormData $data): array
    {
        $mediaFile = $data->mediaFile;

        if ($mediaFile !== null) {
            return [
                'portfolio_id' => (string) ($mediaFile->portfolio_id ?? ''),
                'collection' => $mediaFile->collection,
                'visibility' => $mediaFile->visibility,
                'title_en' => $mediaFile->title_en ?? '',
                'title_ar' => $mediaFile->title_ar ?? '',
                'alt_text_en' => $mediaFile->alt_text_en ?? '',
                'alt_text_ar' => $mediaFile->alt_text_ar ?? '',
            ];
        }

        $portfolioId = $data->defaults['portfolio_id'] ?? $data->actor->portfolio_id ?? '';

        return [
            'portfolio_id' => is_scalar($portfolioId) ? (string) $portfolioId : '',
            'collection' => 'default',
            'visibility' => 'public',
            'title_en' => '',
            'title_ar' => '',
            'alt_text_en' => '',
            'alt_text_ar' => '',
            'file' => null,
        ];
    }
}
