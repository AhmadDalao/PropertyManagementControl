<?php

namespace App\Modules\SystemReadiness\Actions;

use App\Modules\Exports\Support\XlsxWorkbook;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class ReadinessWorkbookExport
{
    public function __construct(private XlsxWorkbook $workbook) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): BinaryFileResponse
    {
        $path = $this->workbook->createSheets($this->sheets($data));

        return response()->download(
            $path,
            'launch-readiness-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{name:string,rows:array<int,array<int,mixed>>}>
     */
    private function sheets(array $data): array
    {
        $sheets = [
            [
                'name' => 'Summary',
                'rows' => $this->summaryRows($data),
            ],
            [
                'name' => 'Infrastructure',
                'rows' => $this->automaticRows($data['systemChecks']),
            ],
            [
                'name' => 'Evidence',
                'rows' => $this->confirmationRows($data['systemConfirmations']),
            ],
        ];

        if (is_array($data['portfolioReadiness'] ?? null)) {
            $sheets[] = [
                'name' => 'Portfolio',
                'rows' => $this->portfolioRows($data),
            ];
        }

        return $sheets;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, mixed>>
     */
    private function summaryRows(array $data): array
    {
        $portfolio = $data['portfolioReadiness']['portfolio'] ?? null;

        return [
            [trans('app.readiness.report_title')],
            [trans('app.readiness.report_generated_at', ['date' => $data['generatedAt']])],
            [trans('app.readiness.report_generated_by', ['name' => $data['preparedBy']])],
            [trans('app.readiness.report_scope', [
                'scope' => is_array($portfolio)
                    ? $portfolio['name'].' · '.$portfolio['code']
                    : trans('app.readiness.report_all_portfolios'),
            ])],
            [],
            [trans('app.readiness.report_status'), trans('app.readiness.report_value')],
            [trans('app.readiness.ready'), $data['summary']['ready']],
            [trans('app.readiness.attention'), $data['summary']['attention']],
            [trans('app.readiness.blocked'), $data['summary']['blocked']],
            [trans('app.readiness.report_total_checks'), $data['summary']['total']],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $checks
     * @return array<int, array<int, mixed>>
     */
    private function automaticRows(array $checks): array
    {
        $rows = [[
            trans('app.readiness.report_check'),
            trans('app.readiness.report_status'),
            trans('app.readiness.report_detail'),
            trans('app.readiness.report_description'),
        ]];

        foreach ($checks as $check) {
            $rows[] = [
                $check['label'],
                trans('app.readiness.status_'.$check['status']),
                $check['detail'] ?? '',
                $check['description'] ?? '',
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $checks
     * @return array<int, array<int, mixed>>
     */
    private function confirmationRows(array $checks): array
    {
        $rows = [[
            trans('app.readiness.report_check'),
            trans('app.readiness.report_status'),
            trans('app.readiness.report_evidence_note'),
            trans('app.readiness.report_confirmed_by'),
            trans('app.readiness.report_confirmed_at'),
        ]];

        foreach ($checks as $check) {
            $rows[] = [
                $check['label'],
                $check['is_confirmed']
                    ? trans('app.readiness.confirmed')
                    : trans('app.readiness.evidence_required'),
                $check['evidence'] ?? '',
                $check['confirmed_by'] ?? '',
                $check['confirmed_at'] ?? '',
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, mixed>>
     */
    private function portfolioRows(array $data): array
    {
        $readiness = $data['portfolioReadiness'];
        $portfolio = $readiness['portfolio'];
        $portfolioStatusKey = 'app.status.'.$portfolio['status'];
        $rows = [
            [trans('app.readiness.portfolio'), $portfolio['name']],
            [trans('app.readiness.report_code'), $portfolio['code']],
            [
                trans('app.readiness.report_status'),
                trans()->has($portfolioStatusKey)
                    ? trans($portfolioStatusKey)
                    : $portfolio['status'],
            ],
            [
                trans('app.readiness.showcase_data'),
                trans(
                    $portfolio['is_showcase']
                        ? 'app.readiness.report_yes'
                        : 'app.readiness.report_no',
                ),
            ],
            [],
            [trans('app.readiness.report_metric'), trans('app.readiness.report_value')],
        ];

        foreach ($readiness['metrics'] as $key => $value) {
            $rows[] = [trans('app.readiness.metric_'.$key), $value];
        }

        $rows[] = [];
        array_push($rows, ...$this->automaticRows($readiness['checks']));
        $rows[] = [];
        array_push($rows, ...$this->confirmationRows($data['portfolioConfirmations']));

        return $rows;
    }
}
