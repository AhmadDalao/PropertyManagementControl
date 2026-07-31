<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Exports\Support\SimpleDocx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class OwnerStatementWordExport
{
    public function __construct(private SimpleDocx $documents) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $path = $this->documents->create($this->blocks($data));
        $content = (string) file_get_contents($path);
        @unlink($path);
        $filename = 'owner-statement-'.now()->format('Ymd-His').'.docx';

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
        $summary = $data['summary'];
        $filters = $data['filters'];
        $context = $data['statement'];

        return [
            $this->paragraph('Owner Statement | كشف المالك', 'Title'),
            $this->paragraph($context['portfolio']['en'].' | '.$context['portfolio']['ar']),
            $this->paragraph($context['property']['en'].' | '.$context['property']['ar']),
            $this->paragraph("Period / الفترة: {$filters['date_from']} - {$filters['date_to']}"),
            $this->paragraph("Prepared for / أعد لصالح: {$context['prepared_for']}"),
            $this->paragraph('Financial summary | الملخص المالي', 'Heading1'),
            $this->table([
                ['Metric / البيان', 'Value / القيمة'],
                ['Collected / المحصل', $this->money($summary['revenue'])],
                ['Expenses / المصاريف', $this->money($summary['expenses'])],
                ['Net position / صافي المركز', $this->money($summary['net'])],
                ['Arrears / المتأخرات', $this->money($summary['arrears'])],
                ['Contract balance / رصيد العقود', $this->money($summary['contractBalance'])],
                ['Collection rate / نسبة التحصيل', $this->percentage($summary['collectionRate'])],
                ['Occupancy / الإشغال', $this->percentage($summary['occupancyRate'])],
            ]),
            $this->paragraph('Contracts in arrears | العقود المتأخرة', 'Heading1'),
            $this->table($this->arrearsRows($data['arrearsLeases'])),
            $this->paragraph('Recent payments | أحدث الدفعات', 'Heading1'),
            $this->table($this->paymentRows($data['recentPayments'])),
            $this->paragraph('Maintenance backlog | طلبات الصيانة المتراكمة', 'Heading1'),
            $this->table($this->maintenanceRows($data['maintenanceBacklog'])),
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
     * @param  array<int, array<string, mixed>>  $leases
     * @return array<int, array<int, string>>
     */
    private function arrearsRows(array $leases): array
    {
        $rows = [['Lease / العقد', 'Tenant / المستأجر', 'Property / العقار', 'Balance / الرصيد']];
        foreach ($leases as $lease) {
            $rows[] = [
                (string) $lease['code'],
                (string) ($lease['tenant'] ?? '-'),
                (string) ($lease['asset'] ?? '-'),
                $this->money($lease['arrears_amount'], (string) $lease['currency']),
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
        $rows = [['Reference / المرجع', 'Tenant / المستأجر', 'Date / التاريخ', 'Amount / المبلغ']];
        foreach ($payments as $payment) {
            $rows[] = [
                (string) $payment['reference'],
                (string) ($payment['tenant'] ?? '-'),
                (string) ($payment['received_on'] ?? '-'),
                $this->money($payment['amount'], (string) $payment['currency']),
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
        $rows = [['Request / الطلب', 'Property / العقار', 'Status / الحالة', 'Priority / الأولوية']];
        foreach ($requests as $request) {
            $rows[] = [
                (string) $request['title'],
                (string) ($request['asset'] ?? '-'),
                (string) $request['status'],
                (string) $request['priority'],
            ];
        }

        return $rows;
    }

    private function money(float|int|string $amount, string $currency = 'SAR'): string
    {
        return number_format((float) $amount, 2).' '.$currency;
    }

    private function percentage(float|int|string $value): string
    {
        return number_format((float) $value, 1).'%';
    }
}
