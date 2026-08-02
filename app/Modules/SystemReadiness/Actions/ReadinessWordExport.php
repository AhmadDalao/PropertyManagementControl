<?php

namespace App\Modules\SystemReadiness\Actions;

use App\Modules\Exports\Support\SimpleDocx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ReadinessWordExport
{
    public function __construct(private SimpleDocx $documents) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $path = $this->documents->create($this->blocks($data));
        $content = (string) file_get_contents($path);
        @unlink($path);

        return response()->streamDownload(
            static fn () => print ($content),
            'launch-readiness-'.now()->format('Ymd-His').'.docx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{type:string,text?:string,style?:string,rows?:array<int,array<int,string>>}>
     */
    private function blocks(array $data): array
    {
        $portfolio = $data['portfolioReadiness']['portfolio'] ?? null;

        return [
            $this->paragraph(trans('app.readiness.report_title'), 'Title'),
            $this->paragraph(trans('app.readiness.report_generated_at', [
                'date' => $data['generatedAt'],
            ])),
            $this->paragraph(trans('app.readiness.report_generated_by', [
                'name' => $data['preparedBy'],
            ])),
            $this->paragraph(trans('app.readiness.report_scope', [
                'scope' => is_array($portfolio)
                    ? $portfolio['name'].' · '.$portfolio['code']
                    : trans('app.readiness.report_all_portfolios'),
            ])),
            $this->paragraph(trans('app.readiness.current_decision'), 'Heading1'),
            $this->table($this->summaryRows($data['summary'])),
            $this->paragraph(trans('app.readiness.report_system_checks'), 'Heading1'),
            $this->table($this->automaticRows($data['systemChecks'])),
            $this->paragraph(trans('app.readiness.report_evidence'), 'Heading1'),
            $this->table($this->confirmationRows($data['systemConfirmations'])),
            ...$this->portfolioBlocks($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{type:string,text?:string,style?:string,rows?:array<int,array<int,string>>}>
     */
    private function portfolioBlocks(array $data): array
    {
        $readiness = $data['portfolioReadiness'] ?? null;

        if (! is_array($readiness)) {
            return [];
        }

        return [
            $this->paragraph(trans('app.readiness.report_portfolio_metrics'), 'Heading1'),
            $this->table($this->metricRows($readiness['metrics'])),
            $this->paragraph(trans('app.readiness.report_portfolio_checks'), 'Heading1'),
            $this->table($this->automaticRows($readiness['checks'])),
            $this->paragraph(trans('app.readiness.portfolio_approvals'), 'Heading1'),
            $this->table($this->confirmationRows($data['portfolioConfirmations'])),
        ];
    }

    /**
     * @param  array<string, int>  $summary
     * @return array<int, array<int, string>>
     */
    private function summaryRows(array $summary): array
    {
        return [
            [trans('app.readiness.report_status'), trans('app.readiness.report_value')],
            [trans('app.readiness.ready'), (string) $summary['ready']],
            [trans('app.readiness.attention'), (string) $summary['attention']],
            [trans('app.readiness.blocked'), (string) $summary['blocked']],
            [trans('app.readiness.report_total_checks'), (string) $summary['total']],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $checks
     * @return array<int, array<int, string>>
     */
    private function automaticRows(array $checks): array
    {
        $rows = [[
            trans('app.readiness.report_check'),
            trans('app.readiness.report_status'),
            trans('app.readiness.report_detail'),
        ]];

        foreach ($checks as $check) {
            $rows[] = [
                (string) $check['label'],
                trans('app.readiness.status_'.$check['status']),
                (string) ($check['detail'] ?? $check['description'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $checks
     * @return array<int, array<int, string>>
     */
    private function confirmationRows(array $checks): array
    {
        $rows = [[
            trans('app.readiness.report_check'),
            trans('app.readiness.report_status'),
            trans('app.readiness.report_evidence_note'),
            trans('app.readiness.report_confirmed_by'),
        ]];

        foreach ($checks as $check) {
            $rows[] = [
                (string) $check['label'],
                $check['is_confirmed']
                    ? trans('app.readiness.confirmed')
                    : trans('app.readiness.evidence_required'),
                (string) ($check['evidence'] ?: trans('app.readiness.report_not_recorded')),
                trim((string) ($check['confirmed_by'] ?? '').' '.(string) ($check['confirmed_at'] ?? '')),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, int>  $metrics
     * @return array<int, array<int, string>>
     */
    private function metricRows(array $metrics): array
    {
        $rows = [[
            trans('app.readiness.report_metric'),
            trans('app.readiness.report_value'),
        ]];

        foreach ($metrics as $key => $value) {
            $rows[] = [
                trans('app.readiness.metric_'.$key),
                (string) $value,
            ];
        }

        return $rows;
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
     * @param  array<int, array<int, string>>  $rows
     * @return array{type:string,rows:array<int,array<int,string>>}
     */
    private function table(array $rows): array
    {
        return ['type' => 'table', 'rows' => $rows];
    }
}
