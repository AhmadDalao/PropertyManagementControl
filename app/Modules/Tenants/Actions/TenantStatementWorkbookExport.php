<?php

namespace App\Modules\Tenants\Actions;

use App\Modules\Exports\Support\XlsxWorkbook;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class TenantStatementWorkbookExport
{
    public function __construct(private XlsxWorkbook $workbook) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): BinaryFileResponse
    {
        $path = $this->workbook->create($this->rows($data));
        $filename = 'tenant-account-'.$data['tenant']['id'].'-'.now()->format('Ymd-His').'.xlsx';

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
            ['Tenant Account Statement | كشف حساب المستأجر'],
            ['Tenant / المستأجر', $data['tenant']['name']],
            ['Period / الفترة', $data['filters']['date_from'], $data['filters']['date_to']],
            [],
            ['Financial position | المركز المالي'],
            ['Currency / العملة', 'Scheduled / المجدول', 'Paid / المسدد', 'Received / المقبوض', 'Balance / الرصيد', 'Overdue / المتأخر'],
        ];

        foreach ($data['statement']['financials'] as $item) {
            $rows[] = [
                $item['currency'],
                $item['scheduled_due'],
                $item['scheduled_paid'],
                $item['received'],
                $item['contract_balance'],
                $item['overdue'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Contracts | العقود'];
        $rows[] = ['Code / الرمز', 'Asset EN', 'Asset AR', 'Start / البداية', 'End / النهاية', 'Status / الحالة', 'Due / المستحق', 'Paid / المسدد', 'Balance / الرصيد', 'Overdue / المتأخر', 'Currency / العملة'];
        foreach ($data['leases'] as $lease) {
            $rows[] = [
                $lease['code'],
                $lease['asset_en'],
                $lease['asset_ar'],
                $lease['started_at'],
                $lease['ends_at'],
                $lease['status'],
                $lease['total_due'],
                $lease['total_paid'],
                $lease['balance'],
                $lease['overdue'],
                $lease['currency'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Installments | الأقساط'];
        $rows[] = ['Due date / الاستحقاق', 'Lease / العقد', 'Label / البيان', 'Status / الحالة', 'Due / المستحق', 'Paid / المسدد', 'Remaining / المتبقي', 'Currency / العملة'];
        foreach ($data['installments'] as $installment) {
            $rows[] = [
                $installment['due_date'],
                $installment['lease_code'],
                $installment['label'],
                $installment['status'],
                $installment['amount_due'],
                $installment['amount_paid'],
                $installment['remaining'],
                $installment['currency'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Payments | الدفعات'];
        $rows[] = ['Date / التاريخ', 'Reference / المرجع', 'Lease / العقد', 'Method / الطريقة', 'Status / الحالة', 'Amount / المبلغ', 'Currency / العملة'];
        foreach ($data['payments'] as $payment) {
            $rows[] = [
                $payment['received_on'],
                $payment['reference'],
                $payment['lease_code'],
                $payment['method'],
                $payment['status'],
                $payment['amount'],
                $payment['currency'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Maintenance | الصيانة'];
        $rows[] = ['Date / التاريخ', 'Request / الطلب', 'Asset EN', 'Asset AR', 'Status / الحالة', 'Priority / الأولوية'];
        foreach ($data['maintenance'] as $request) {
            $rows[] = [
                $request['requested_at'],
                $request['title'],
                $request['asset_en'],
                $request['asset_ar'],
                $request['status'],
                $request['priority'],
            ];
        }

        return $rows;
    }
}
