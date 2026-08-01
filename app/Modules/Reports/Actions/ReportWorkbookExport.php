<?php

namespace App\Modules\Reports\Actions;

use App\Models\User;
use App\Modules\Exports\Support\XlsxWorkbook;
use App\Modules\Reports\Support\ReportPropertyScope;
use App\Modules\Wording\UiTranslationCatalog;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportWorkbookExport
{
    public function __construct(
        private readonly XlsxWorkbook $workbook,
        private readonly UiTranslationCatalog $translations,
        private readonly ReportPropertyScope $properties,
    ) {}

    /**
     * @param  array<string, mixed>  $report
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     */
    public function download(array $report, array $filters, User $actor): BinaryFileResponse
    {
        $filename = 'portfolio-report-'.now()->format('Ymd-His').'.xlsx';
        $property = $this->properties->label(
            $actor,
            $filters['portfolio_id'],
            $filters['property_id'],
        );
        $path = $this->workbook->create($this->rows($report, $filters, $property));

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     * @return array<int, array<int, mixed>>
     */
    private function rows(array $report, array $filters, ?string $property): array
    {
        $rows = [
            [$this->copy('Property Management Control Report')],
            [$this->copy('Date From'), $filters['date_from']],
            [$this->copy('Date To'), $filters['date_to']],
            [trans('app.reports.property'), $property ?? trans('app.reports.all_properties')],
            [],
            $this->labels(['Section', 'Metric', 'Value']),
        ];

        foreach ([
            'occupancyRate',
            'activeLeases',
            'leasesInArrears',
            'openRequests',
            'resolvedRequests',
            'openCollectionCount',
            'untrackedOverdueCount',
            'followUpDueCount',
            'brokenPromisesCount',
        ] as $metric) {
            $rows[] = [
                $this->copy('Summary'),
                $this->metric($metric),
                $report['summary'][$metric] ?? 0,
            ];
        }

        $rows[] = [];
        $rows[] = $this->labels([
            'Currency Position',
            'Currency',
            'Collected',
            'Expenses',
            'Net Position',
            'Scheduled Due',
            'Scheduled Paid',
            'Collection Rate',
            'Arrears',
            'Contract Balance',
        ]);
        foreach ($report['summary']['currencyTotals'] as $position) {
            $rows[] = [
                $this->copy('Currency Position'),
                $position['currency'],
                $position['revenue'],
                $position['expenses'],
                $position['net'],
                $position['scheduledDue'],
                $position['scheduledPaid'],
                $position['collectionRate'],
                $position['arrears'],
                $position['contractBalance'],
            ];
        }

        $rows = [
            ...$rows,
            ...$this->comparisonRows($report['comparison']),
        ];

        $rows[] = [];
        $rows[] = $this->labels(['Revenue by Month', 'Month', 'Currency', 'Amount']);
        foreach ($report['charts']['revenueByMonth'] as $item) {
            $rows[] = [
                $this->copy('Revenue by Month'),
                $item['label'],
                $item['currency'],
                $item['amount'],
            ];
        }

        $rows[] = [];
        $rows[] = $this->labels(['Expense by Category', 'Category', 'Currency', 'Amount']);
        foreach ($report['charts']['expenseByCategory'] as $item) {
            $rows[] = [
                $this->copy('Expense by Category'),
                $this->option((string) $item['label']),
                $item['currency'],
                $item['amount'],
            ];
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

        $rows[] = [];
        $rows[] = [
            trans('app.reports.journal_title'),
            ...$this->labels([
                'Date',
                'Type',
                'Record',
                'Context',
                'Performed By',
                'Amount',
                'Currency',
            ]),
        ];
        foreach ($report['operationalJournal'] as $event) {
            $rows[] = [
                trans('app.reports.journal_title'),
                $event['occurred_at'],
                $event['type_label'],
                $event['title'],
                $event['subtitle'],
                $event['actor'],
                $event['amount'],
                $event['currency'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $comparison
     * @return array<int, array<int, mixed>>
     */
    private function comparisonRows(array $comparison): array
    {
        $period = $comparison['period'];
        $rows = [
            [],
            [
                trans('app.reports.comparison_title'),
                trans('app.reports.comparison_period'),
                $period['date_from'],
                $period['date_to'],
            ],
            [
                trans('app.reports.comparison_title'),
                trans('app.reports.currency'),
                $this->copy('Metric'),
                trans('app.reports.current_period_value'),
                trans('app.reports.previous_period_value'),
                trans('app.reports.change'),
                trans('app.reports.change_type'),
            ],
        ];

        foreach ($comparison['currencyPositions'] as $position) {
            foreach ($position['metrics'] as $metric) {
                $rows[] = $this->comparisonMetricRow(
                    $metric,
                    (string) $position['currency'],
                );
            }
        }

        foreach ($comparison['serviceMetrics'] as $metric) {
            $rows[] = $this->comparisonMetricRow(
                $metric,
                trans('app.reports.maintenance_activity'),
            );
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $metric
     * @return array<int, mixed>
     */
    private function comparisonMetricRow(array $metric, string $group): array
    {
        return [
            trans('app.reports.comparison_title'),
            $group,
            trans("app.reports.{$metric['key']}"),
            $metric['current'],
            $metric['previous'],
            $metric['change'] ?? trans('app.reports.change_new_activity'),
            trans(
                $metric['changeKind'] === 'points'
                    ? 'app.reports.change_points_label'
                    : 'app.reports.change_percent_label',
            ),
        ];
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

    private function metric(string $metric): string
    {
        $key = "app.reports.metric_{$metric}";

        return trans()->has($key)
            ? trans($key)
            : $this->copy(str($metric)->snake(' ')->title()->toString());
    }

    private function option(string $value): string
    {
        $statusKey = "app.status.{$value}";

        return trans()->has($statusKey)
            ? trans($statusKey)
            : $this->copy(str($value)->replace('_', ' ')->title()->toString());
    }
}
