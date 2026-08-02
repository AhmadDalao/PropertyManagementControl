<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Exports\Support\XlsxWorkbook;
use App\Modules\Reports\Presenters\ArrearsAgingExportRows;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class ArrearsAgingWorkbookExport
{
    public function __construct(
        private XlsxWorkbook $workbook,
        private ArrearsAgingExportRows $rows,
    ) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): BinaryFileResponse
    {
        $summary = [
            ['Arrears Aging | تحليل أعمار المتأخرات'],
            ['Current snapshot / الحالة الحالية', today()->toDateString()],
            [],
            ...$this->rows->scope($data['scope']),
            [],
            ...$this->rows->currencies($data['currencyPositions']),
        ];
        $path = $this->workbook->createSheets([
            ['name' => 'Aging Summary', 'rows' => $summary],
            ['name' => 'Aging Detail', 'rows' => $this->rows->records($data['records'])],
        ]);

        return response()->download(
            $path,
            'arrears-aging-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }
}
