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
            $this->table($this->currencyRows($summary['currencyTotals'])),
            $this->table([
                ['Metric / البيان', 'Value / القيمة'],
                ['Occupancy / الإشغال', $this->percentage($summary['occupancyRate'])],
                ['Active leases / العقود النشطة', (string) $summary['activeLeases']],
                ['Open maintenance / الصيانة المفتوحة', (string) $summary['openRequests']],
            ]),
            $this->paragraph('Period comparison | مقارنة الفترات', 'Heading1'),
            $this->paragraph(
                "Previous period / الفترة السابقة: {$data['comparison']['period']['date_from']} - {$data['comparison']['period']['date_to']}",
            ),
            $this->table($this->comparisonRows($data['comparison'])),
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

    /**
     * @param  array<int, array<string, mixed>>  $positions
     * @return array<int, array<int, string>>
     */
    private function currencyRows(array $positions): array
    {
        $rows = [[
            'Currency / العملة',
            'Collected / المحصل',
            'Expenses / المصاريف',
            'Net / الصافي',
            'Arrears / المتأخرات',
            'Contract balance / رصيد العقود',
            'Collection / التحصيل',
        ]];

        foreach ($positions as $position) {
            $currency = (string) $position['currency'];
            $rows[] = [
                $currency,
                $this->money($position['revenue'], $currency),
                $this->money($position['expenses'], $currency),
                $this->money($position['net'], $currency),
                $this->money($position['arrears'], $currency),
                $this->money($position['contractBalance'], $currency),
                $this->percentage($position['collectionRate']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $comparison
     * @return array<int, array<int, string>>
     */
    private function comparisonRows(array $comparison): array
    {
        $rows = [[
            'Group / المجموعة',
            'Metric / المؤشر',
            'Current / الحالي',
            'Previous / السابق',
            'Change / التغير',
        ]];

        foreach ($comparison['currencyPositions'] as $position) {
            foreach ($position['metrics'] as $metric) {
                $rows[] = $this->comparisonMetricRow(
                    (string) $position['currency'],
                    $metric,
                    (string) $position['currency'],
                );
            }
        }

        foreach ($comparison['serviceMetrics'] as $metric) {
            $rows[] = $this->comparisonMetricRow(
                'Maintenance / الصيانة',
                $metric,
            );
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $metric
     * @return array<int, string>
     */
    private function comparisonMetricRow(
        string $group,
        array $metric,
        ?string $currency = null,
    ): array {
        return [
            $group,
            trans("app.reports.{$metric['key']}", locale: 'en')
                .' / '
                .trans("app.reports.{$metric['key']}", locale: 'ar'),
            $this->comparisonValue($metric['current'], $metric['format'], $currency),
            $this->comparisonValue($metric['previous'], $metric['format'], $currency),
            $this->comparisonChange($metric),
        ];
    }

    private function comparisonValue(
        float|int|string $value,
        string $format,
        ?string $currency,
    ): string {
        return match ($format) {
            'money' => $this->money($value, $currency ?? 'SAR'),
            'percent' => $this->percentage($value),
            default => number_format((float) $value, 0),
        };
    }

    /** @param array<string, mixed> $metric */
    private function comparisonChange(array $metric): string
    {
        if ($metric['change'] === null) {
            return 'New activity / نشاط جديد';
        }

        $suffix = $metric['changeKind'] === 'points'
            ? ' pp / نقطة مئوية'
            : '%';

        return number_format((float) $metric['change'], 1).$suffix;
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
