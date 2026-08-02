<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Exports\Support\SimpleDocx;
use App\Modules\Reports\Presenters\ArrearsAgingExportRows;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ArrearsAgingWordExport
{
    public function __construct(
        private SimpleDocx $documents,
        private ArrearsAgingExportRows $rows,
    ) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $path = $this->documents->create([
            $this->paragraph('Arrears Aging | تحليل أعمار المتأخرات', 'Title'),
            $this->paragraph('Current snapshot / الحالة الحالية: '.today()->toDateString()),
            $this->paragraph('Scope | النطاق', 'Heading1'),
            $this->table($this->rows->scope($data['scope'])),
            $this->paragraph('Aging by currency | أعمار المتأخرات حسب العملة', 'Heading1'),
            $this->table($this->rows->currencies($data['currencyPositions'])),
            $this->paragraph('Overdue schedule | جدول المتأخرات', 'Heading1'),
            $this->table($this->rows->records($data['records'])),
        ]);
        $content = (string) file_get_contents($path);
        @unlink($path);

        return response()->streamDownload(
            static fn () => print ($content),
            'arrears-aging-'.now()->format('Ymd-His').'.docx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        );
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
