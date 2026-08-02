<?php

namespace App\Modules\OpeningData\Presenters;

use App\Models\User;
use App\Modules\OpeningData\Support\OpeningDataAccess;
use App\Modules\OpeningData\Support\OpeningDataPreviewStore;
use App\Modules\OpeningData\Support\OpeningDataWorkbookSchema;
use Illuminate\Validation\ValidationException;

final class OpeningDataPagePresenter
{
    public function __construct(
        private readonly OpeningDataAccess $access,
        private readonly OpeningDataPreviewStore $previews,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ?string $previewToken): array
    {
        $portfolios = $this->access->options($actor);
        $preview = null;

        if (is_string($previewToken) && $previewToken !== '') {
            try {
                $manifest = $this->previews->load($actor, $previewToken);
                $preview = $this->previews->publicPayload($previewToken, $manifest);
            } catch (ValidationException) {
                $preview = null;
            }
        }

        return [
            'openingData' => [
                'portfolios' => $portfolios,
                'preview' => $preview,
                'limits' => OpeningDataWorkbookSchema::ROW_LIMITS,
                'maxFileMegabytes' => 10,
            ],
        ];
    }
}
