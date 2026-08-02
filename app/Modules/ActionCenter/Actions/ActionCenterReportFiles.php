<?php

namespace App\Modules\ActionCenter\Actions;

use App\Modules\ActionCenter\Presenters\ActionCenterExportRows;
use App\Modules\Documents\Support\BilingualPdf;
use App\Modules\Exports\Support\SimpleDocx;
use App\Modules\Exports\Support\XlsxWorkbook;

final readonly class ActionCenterReportFiles
{
    public const int MAX_PDF_DETAIL_ROWS = 100;

    public function __construct(
        private BilingualPdf $pdf,
        private SimpleDocx $documents,
        private XlsxWorkbook $workbook,
        private ActionCenterExportRows $rows,
    ) {}

    /** @param array<string, mixed> $data */
    public function pdf(array $data): string
    {
        $records = is_array($data['records'] ?? null) ? $data['records'] : [];
        $data['recordTotal'] = count($records);
        $data['records'] = array_slice($records, 0, self::MAX_PDF_DETAIL_ROWS);
        $data['recordLimit'] = self::MAX_PDF_DETAIL_ROWS;

        return $this->pdf
            ->loadView('pdf.daily-operations-brief', ['data' => $data])
            ->setPaper('a4', 'landscape')
            ->output();
    }

    /** @param array<string, mixed> $data */
    public function docx(array $data): string
    {
        $content = [
            $this->paragraph('Daily Operations Brief | موجز العمليات اليومية', 'Title'),
            $this->paragraph('Current snapshot / الحالة الحالية: '.now()->format('Y-m-d H:i')),
            $this->paragraph('Applied scope | النطاق المطبق', 'Heading1'),
            $this->table($this->rows->scope($data['scope'])),
            $this->paragraph('Priority position | موقف الأولويات', 'Heading1'),
            $this->table($this->rows->summary($data['summary'])),
            $this->paragraph('Work by type | الأعمال حسب النوع', 'Heading1'),
            $this->table($this->rows->types($data['typePositions'])),
        ];

        if ($data['currencyPositions'] !== []) {
            $content[] = $this->paragraph('Financial exposure | المبالغ المرتبطة', 'Heading1');
            $content[] = $this->table($this->rows->currencies($data['currencyPositions']));
        }

        $content[] = $this->paragraph('Priority queue | قائمة الأولويات', 'Heading1');
        $content[] = $this->table($this->rows->records($data['records']));
        $path = $this->documents->create($content);
        $binary = (string) file_get_contents($path);
        @unlink($path);

        return $binary;
    }

    /** @param array<string, mixed> $data */
    public function xlsx(array $data): string
    {
        $summary = [
            ...$this->rows->summary($data['summary']),
            [],
            ...$this->rows->types($data['typePositions']),
        ];

        if ($data['currencyPositions'] !== []) {
            $summary = [
                ...$summary,
                [],
                ...$this->rows->currencies($data['currencyPositions']),
            ];
        }

        $path = $this->workbook->createSheets([
            ['name' => 'Queue - الأولويات', 'rows' => $this->rows->records($data['records'])],
            ['name' => 'Scope - النطاق', 'rows' => $this->rows->scope($data['scope'])],
            ['name' => 'Summary - الملخص', 'rows' => $summary],
        ]);
        $binary = (string) file_get_contents($path);
        @unlink($path);

        return $binary;
    }

    /** @return array{type:string,text:string,style?:string} */
    private function paragraph(string $text, ?string $style = null): array
    {
        return array_filter([
            'type' => 'paragraph',
            'text' => $text,
            'style' => $style,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{type:string,rows:array<int, array<int, string>>}
     */
    private function table(array $rows): array
    {
        return [
            'type' => 'table',
            'rows' => array_map(
                static fn (array $row): array => array_map(
                    static fn (mixed $value): string => (string) $value,
                    $row,
                ),
                $rows,
            ),
        ];
    }
}
