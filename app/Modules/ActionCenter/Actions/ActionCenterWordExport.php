<?php

namespace App\Modules\ActionCenter\Actions;

use App\Modules\ActionCenter\Presenters\ActionCenterExportRows;
use App\Modules\Exports\Support\SimpleDocx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ActionCenterWordExport
{
    public function __construct(
        private SimpleDocx $documents,
        private ActionCenterExportRows $rows,
    ) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
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

        return response()->streamDownload(
            static fn () => print ($binary),
            'daily-operations-brief-'.now()->format('Ymd-His').'.docx',
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
