<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Wording\UiTranslationCatalog;
use App\Services\XlsxWorkbook;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportWorkbookExport
{
    public function __construct(
        private readonly XlsxWorkbook $workbook,
        private readonly UiTranslationCatalog $translations,
    ) {}

    /**
     * @param  array<string, mixed>  $report
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null}  $filters
     */
    public function download(array $report, array $filters): BinaryFileResponse
    {
        $filename = 'portfolio-report-'.now()->format('Ymd-His').'.xlsx';
        $path = $this->workbook->create($this->rows($report, $filters));

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null}  $filters
     * @return array<int, array<int, mixed>>
     */
    private function rows(array $report, array $filters): array
    {
        $rows = [
            [$this->copy('Property Management Control Report')],
            [$this->copy('Date From'), $filters['date_from']],
            [$this->copy('Date To'), $filters['date_to']],
            [],
            $this->labels(['Section', 'Metric', 'Value']),
        ];

        foreach ($report['summary'] as $metric => $value) {
            $rows[] = [
                $this->copy('Summary'),
                $this->copy(str($metric)->snake(' ')->title()->toString()),
                $value,
            ];
        }

        $rows[] = [];
        $rows[] = $this->labels(['Revenue by Month', 'Month', 'Amount']);
        foreach ($report['charts']['revenueByMonth'] as $month => $amount) {
            $rows[] = [$this->copy('Revenue by Month'), $month, $amount];
        }

        $rows[] = [];
        $rows[] = $this->labels(['Expense by Category', 'Category', 'Amount']);
        foreach ($report['charts']['expenseByCategory'] as $category => $amount) {
            $rows[] = [$this->copy('Expense by Category'), $this->option((string) $category), $amount];
        }

        $rows[] = [];
        $rows[] = $this->labels(['Arrears', 'Lease', 'Tenant', 'Asset', 'Balance', 'Currency']);
        foreach ($report['arrearsLeases'] as $lease) {
            $rows[] = [
                $this->copy('Arrears'),
                $lease['code'],
                $lease['tenant'],
                $lease['asset'],
                $lease['arrears_amount'],
                $lease['currency'],
            ];
        }

        $rows[] = [];
        $rows[] = $this->labels(['Maintenance Backlog', 'ID', 'Title', 'Asset', 'Status', 'Priority']);
        foreach ($report['maintenanceBacklog'] as $request) {
            $rows[] = [
                $this->copy('Maintenance Backlog'),
                $request['id'],
                $request['title'],
                $request['asset'],
                $this->option($request['status']),
                $this->option($request['priority']),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $labels
     * @return array<int, string>
     */
    private function labels(array $labels): array
    {
        return array_map(fn (string $label): string => $this->copy($label), $labels);
    }

    private function copy(string $value): string
    {
        return $this->translations->text($value);
    }

    private function option(string $value): string
    {
        $statusKey = "app.status.{$value}";

        return trans()->has($statusKey)
            ? trans($statusKey)
            : $this->copy(str($value)->replace('_', ' ')->title()->toString());
    }
}
