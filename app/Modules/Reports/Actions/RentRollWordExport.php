<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Exports\Support\SimpleDocx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class RentRollWordExport
{
    public function __construct(private SimpleDocx $documents) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $path = $this->documents->create($this->blocks($data));
        $content = (string) file_get_contents($path);
        @unlink($path);
        $filename = 'rent-roll-'.now()->format('Ymd-His').'.docx';

        return response()->streamDownload(
            static fn () => print ($content),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{type:string,text?:string,style?:string,rows?:array<int,array<int,string>>}>
     */
    private function blocks(array $data): array
    {
        return [
            $this->paragraph('Rent Roll | سجل الإيجارات', 'Title'),
            $this->paragraph('Current snapshot / الحالة الحالية: '.today()->toDateString()),
            $this->paragraph('Scope | النطاق', 'Heading1'),
            $this->table($this->scopeRows($data['scope'])),
            $this->paragraph('Currency positions | المراكز حسب العملة', 'Heading1'),
            $this->table($this->currencyRows($data['currencyPositions'])),
            $this->paragraph('Rentable records | السجلات القابلة للتأجير', 'Heading1'),
            $this->table($this->recordRows($data['records'])),
        ];
    }

    /** @return array{type:string,text:string,style?:string} */
    private function paragraph(string $text, ?string $style = null): array
    {
        $paragraph = ['type' => 'paragraph', 'text' => $text];

        if ($style !== null) {
            $paragraph['style'] = $style;
        }

        return $paragraph;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array{type:string,rows:array<int,array<int,string>>}
     */
    private function table(array $rows): array
    {
        return ['type' => 'table', 'rows' => $rows];
    }

    /**
     * @param  array<int, array{label:string,value:string}>  $scope
     * @return array<int, array<int, string>>
     */
    private function scopeRows(array $scope): array
    {
        $rows = [['Scope / النطاق', 'Value / القيمة']];

        foreach ($scope as $item) {
            $rows[] = [(string) $item['label'], (string) $item['value']];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $positions
     * @return array<int, array<int, string>>
     */
    private function currencyRows(array $positions): array
    {
        $rows = [[
            'Currency / العملة',
            'Active / النشطة',
            'Contracted / المتعاقد',
            'Paid / المسدد',
            'Outstanding / المتبقي',
            'Overdue / المتأخر',
            'Deposits / التأمينات',
        ]];

        foreach ($positions as $position) {
            $currency = (string) $position['currency'];
            $rows[] = [
                $currency,
                (string) $position['active_leases'],
                $this->money($position['contracted'], $currency),
                $this->money($position['paid'], $currency),
                $this->money($position['outstanding'], $currency),
                $this->money($position['overdue'], $currency),
                $this->money($position['deposits'], $currency),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<int, string>>
     */
    private function recordRows(array $records): array
    {
        $rows = [[
            'Property / العقار',
            'Space / الوحدة',
            'Tenant / المستأجر',
            'Lease / العقد',
            'Period / المدة',
            'Rent / الإيجار',
            'Paid / المسدد',
            'Outstanding / المتبقي',
            'Overdue / المتأخر',
            'State / الحالة',
        ]];

        foreach ($records as $record) {
            $lease = $record['lease'];
            $rows[] = [
                $this->localized($record['property'] ?? []),
                $this->localized($record),
                (string) ($lease['tenant'] ?? '-'),
                (string) ($lease['code'] ?? '-'),
                $lease
                    ? ($lease['started_at'] ?: '-').' - '.($lease['ends_at'] ?: '-')
                    : '-',
                $lease ? $this->money($lease['rent_amount'], $lease['currency']) : '-',
                $lease ? $this->money($lease['total_paid'], $lease['currency']) : '-',
                $lease ? $this->money($lease['balance'], $lease['currency']) : '-',
                $lease ? $this->money($lease['overdue'], $lease['currency']) : '-',
                trans("app.reports.rent_roll_state_{$record['state']}", locale: 'en')
                    .' / '
                    .trans("app.reports.rent_roll_state_{$record['state']}", locale: 'ar'),
            ];
        }

        return $rows;
    }

    /** @param array<string, mixed> $record */
    private function localized(array $record): string
    {
        return implode(' / ', array_filter([
            $record['title_en'] ?? null,
            $record['title_ar'] ?? null,
            $record['code'] ?? null,
        ]));
    }

    private function money(float|int|string $amount, string $currency): string
    {
        return number_format((float) $amount, 2).' '.$currency;
    }
}
