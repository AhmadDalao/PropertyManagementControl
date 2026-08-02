<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Exports\Support\XlsxWorkbook;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class RentRollWorkbookExport
{
    public function __construct(private XlsxWorkbook $workbook) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): BinaryFileResponse
    {
        $path = $this->workbook->create($this->rows($data));
        $filename = 'rent-roll-'.now()->format('Ymd-His').'.xlsx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, mixed>>
     */
    private function rows(array $data): array
    {
        $rows = [
            ['Rent Roll | سجل الإيجارات'],
            ['Current snapshot / الحالة الحالية', today()->toDateString()],
        ];

        foreach ($data['scope'] as $item) {
            $rows[] = [$item['label'], $item['value']];
        }

        $rows[] = [];
        $rows[] = ['Currency positions | المراكز حسب العملة'];
        $rows[] = [
            'Currency / العملة',
            'Active leases / العقود النشطة',
            'Contracted / المتعاقد',
            'Paid / المسدد',
            'Outstanding / المتبقي',
            'Overdue / المتأخر',
            'Deposits / التأمينات',
        ];

        foreach ($data['currencyPositions'] as $position) {
            $rows[] = [
                $position['currency'],
                $position['active_leases'],
                $position['contracted'],
                $position['paid'],
                $position['outstanding'],
                $position['overdue'],
                $position['deposits'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Rentable records | السجلات القابلة للتأجير'];
        $rows[] = [
            'Portfolio / المحفظة',
            'Property EN',
            'Property AR',
            'Property code / رمز العقار',
            'Space EN',
            'Space AR',
            'Space code / رمز الوحدة',
            'Type / النوع',
            'Usage / الاستخدام',
            'State / الحالة',
            'Tenant / المستأجر',
            'Lease / العقد',
            'Start / البداية',
            'End / النهاية',
            'Frequency / التكرار',
            'Rent / الإيجار',
            'Deposit / التأمين',
            'Contracted / المتعاقد',
            'Paid / المسدد',
            'Outstanding / المتبقي',
            'Overdue / المتأخر',
            'Currency / العملة',
        ];

        foreach ($data['records'] as $record) {
            $lease = $record['lease'];
            $rows[] = [
                $record['portfolio']['name'],
                $record['property']['title_en'] ?? null,
                $record['property']['title_ar'] ?? null,
                $record['property']['code'] ?? null,
                $record['title_en'],
                $record['title_ar'],
                $record['code'],
                $record['asset_type'],
                $record['usage_type'],
                trans("app.reports.rent_roll_state_{$record['state']}"),
                $lease['tenant'] ?? null,
                $lease['code'] ?? null,
                $lease['started_at'] ?? null,
                $lease['ends_at'] ?? null,
                $lease['payment_frequency'] ?? null,
                $lease['rent_amount'] ?? null,
                $lease['deposit_amount'] ?? null,
                $lease['total_due'] ?? null,
                $lease['total_paid'] ?? null,
                $lease['balance'] ?? null,
                $lease['overdue'] ?? null,
                $lease['currency'] ?? null,
            ];
        }

        return $rows;
    }
}
