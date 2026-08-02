<?php

namespace App\Modules\OpeningData\Actions;

use App\Models\User;
use App\Modules\OpeningData\Support\OpeningDataAccess;
use App\Modules\OpeningData\Support\OpeningDataNormalizer;
use App\Modules\OpeningData\Support\OpeningDataPreviewStore;
use App\Modules\OpeningData\Support\OpeningDataReferenceValidator;
use App\Modules\OpeningData\Support\OpeningDataRowValidator;
use App\Modules\OpeningData\Support\OpeningDataWorkbookSchema;
use App\Modules\OpeningData\Support\XlsxReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class PreviewOpeningData
{
    public function __construct(
        private readonly OpeningDataAccess $access,
        private readonly OpeningDataWorkbookSchema $schema,
        private readonly XlsxReader $reader,
        private readonly OpeningDataNormalizer $normalizer,
        private readonly OpeningDataRowValidator $rows,
        private readonly OpeningDataReferenceValidator $references,
        private readonly OpeningDataPreviewStore $previews,
    ) {}

    public function handle(
        User $actor,
        int $portfolioId,
        UploadedFile $workbook,
    ): string {
        $portfolio = $this->access->portfolio($actor, $portfolioId);
        $path = $workbook->getRealPath();

        if (! is_string($path)) {
            throw ValidationException::withMessages([
                'file' => trans('app.opening_data.errors.invalid_workbook'),
            ]);
        }

        try {
            $tables = $this->reader->tables($path, $this->schema->sheetNames());
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'file' => $exception->getMessage(),
            ]);
        }

        $data = [];
        $normalizedTables = [];

        foreach ($this->schema->sheetNames() as $sheet) {
            $rows = $this->normalizer->rows($sheet, $tables[$sheet]['rows'] ?? []);
            $data[$sheet] = $rows;
            $normalizedTables[$sheet] = [
                'headers' => $tables[$sheet]['headers'] ?? [],
                'rows' => $rows,
            ];
        }

        $issues = $this->rows->validate($normalizedTables);
        $issues = [
            ...$issues,
            ...$this->references->validate($portfolio, $data),
        ];

        return $this->previews->create($actor, $portfolio, $data, $issues);
    }
}
