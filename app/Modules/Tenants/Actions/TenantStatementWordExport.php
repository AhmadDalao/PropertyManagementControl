<?php

namespace App\Modules\Tenants\Actions;

use App\Modules\Exports\Support\SimpleDocx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class TenantStatementWordExport
{
    public function __construct(private SimpleDocx $documents) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $path = $this->documents->create($this->blocks($data));
        $content = (string) file_get_contents($path);
        @unlink($path);
        $filename = 'tenant-account-'.$data['tenant']['id'].'-'.now()->format('Ymd-His').'.docx';

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
        $tenant = $data['tenant'];
        $filters = $data['filters'];
        $rows = [
            $this->paragraph('Tenant Account Statement | كشف حساب المستأجر', 'Title'),
            $this->paragraph($tenant['name']),
            $this->paragraph("Period / الفترة: {$filters['date_from']} - {$filters['date_to']}"),
            $this->paragraph('Financial position | المركز المالي', 'Heading1'),
            $this->table($this->financialRows($data['statement']['financials'])),
            $this->paragraph('Contracts | العقود', 'Heading1'),
            $this->table($this->leaseRows($data['leases'])),
            $this->paragraph('Installments | الأقساط', 'Heading1'),
            $this->table($this->installmentRows($data['installments'])),
            $this->paragraph('Payments | الدفعات', 'Heading1'),
            $this->table($this->paymentRows($data['payments'])),
            $this->paragraph('Maintenance | الصيانة', 'Heading1'),
            $this->table($this->maintenanceRows($data['maintenance'])),
        ];

        return $rows;
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
     * @param  array<int, array<string, mixed>>  $financials
     * @return array<int, array<int, string>>
     */
    private function financialRows(array $financials): array
    {
        $rows = [['Currency / العملة', 'Due / المستحق', 'Paid / المسدد', 'Balance / الرصيد', 'Overdue / المتأخر']];
        foreach ($financials as $item) {
            $rows[] = [
                (string) $item['currency'],
                $this->money($item['scheduled_due'], $item['currency']),
                $this->money($item['scheduled_paid'], $item['currency']),
                $this->money($item['contract_balance'], $item['currency']),
                $this->money($item['overdue'], $item['currency']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $leases
     * @return array<int, array<int, string>>
     */
    private function leaseRows(array $leases): array
    {
        $rows = [['Lease / العقد', 'Asset / الأصل', 'Period / المدة', 'Status / الحالة', 'Balance / الرصيد']];
        foreach ($leases as $lease) {
            $rows[] = [
                (string) $lease['code'],
                (string) ($lease['asset_en'] ?: $lease['asset_ar'] ?: '-'),
                ($lease['started_at'] ?: '-').' - '.($lease['ends_at'] ?: '-'),
                (string) $lease['status'],
                $this->money($lease['balance'], $lease['currency']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $installments
     * @return array<int, array<int, string>>
     */
    private function installmentRows(array $installments): array
    {
        $rows = [['Due / الاستحقاق', 'Lease / العقد', 'Status / الحالة', 'Amount / المبلغ', 'Remaining / المتبقي']];
        foreach ($installments as $installment) {
            $rows[] = [
                (string) ($installment['due_date'] ?? '-'),
                (string) ($installment['lease_code'] ?? '-'),
                (string) $installment['status'],
                $this->money($installment['amount_due'], $installment['currency']),
                $this->money($installment['remaining'], $installment['currency']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $payments
     * @return array<int, array<int, string>>
     */
    private function paymentRows(array $payments): array
    {
        $rows = [['Date / التاريخ', 'Reference / المرجع', 'Lease / العقد', 'Status / الحالة', 'Amount / المبلغ']];
        foreach ($payments as $payment) {
            $rows[] = [
                (string) ($payment['received_on'] ?? '-'),
                (string) $payment['reference'],
                (string) ($payment['lease_code'] ?? '-'),
                (string) $payment['status'],
                $this->money($payment['amount'], $payment['currency']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $requests
     * @return array<int, array<int, string>>
     */
    private function maintenanceRows(array $requests): array
    {
        $rows = [['Date / التاريخ', 'Request / الطلب', 'Asset / الأصل', 'Status / الحالة', 'Priority / الأولوية']];
        foreach ($requests as $request) {
            $rows[] = [
                (string) ($request['requested_at'] ?? '-'),
                (string) $request['title'],
                (string) ($request['asset_en'] ?: $request['asset_ar'] ?: '-'),
                (string) $request['status'],
                (string) $request['priority'],
            ];
        }

        return $rows;
    }

    private function money(float|int|string $amount, string $currency): string
    {
        return number_format((float) $amount, 2).' '.$currency;
    }
}
