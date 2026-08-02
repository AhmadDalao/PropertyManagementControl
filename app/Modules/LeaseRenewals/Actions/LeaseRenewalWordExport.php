<?php

namespace App\Modules\LeaseRenewals\Actions;

use App\Modules\Exports\Support\SimpleDocx;
use App\Modules\LeaseRenewals\Presenters\LeaseRenewalExportRows;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class LeaseRenewalWordExport
{
    public function __construct(
        private SimpleDocx $documents,
        private LeaseRenewalExportRows $rows,
    ) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $path = $this->documents->create([
            $this->paragraph('Lease Expiry & Renewal Schedule | جدول انتهاء وتجديد العقود', 'Title'),
            $this->paragraph('Current snapshot / الحالة الحالية: '.today()->toDateString()),
            $this->paragraph('Applied scope | النطاق المطبق', 'Heading1'),
            $this->table($this->rows->scope($data['scope'])),
            $this->paragraph('Position by currency | الموقف حسب العملة', 'Heading1'),
            $this->table($this->rows->currencies($data['currencyPositions'])),
            $this->paragraph('Renewal schedule | جدول التجديد', 'Heading1'),
            $this->table($this->rows->records($data['records'])),
        ]);
        $content = (string) file_get_contents($path);
        @unlink($path);

        return response()->streamDownload(
            static fn () => print ($content),
            'lease-renewal-schedule-'.now()->format('Ymd-His').'.docx',
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
