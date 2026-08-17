<?php

namespace App\Modules\Reports\Presenters;

use App\Models\ReportPreset;
use App\Modules\Reports\Support\ReportFilterSet;

final readonly class ReportPresetOutputPresenter
{
    public function __construct(private ReportFilterSet $filters) {}

    /**
     * @param  array<string, mixed>  $view
     * @return array<int, array<string, mixed>>
     */
    public function present(
        ReportPreset $preset,
        array $view,
        string $dateRange,
    ): array {
        $stored = $this->filters->stored($preset->filters_json);
        $propertyId = $stored['property_id'] ?? null;
        $scope = $view['scope_label'].' · '.$dateRange;
        $routeParameters = [
            'date_from' => $view['date_from'],
            'date_to' => $view['date_to'],
        ];
        $propertyReport = is_int($propertyId);

        return collect([
            'PDF' => $propertyReport
                ? route('reports.properties.pdf', ['asset' => $propertyId, ...$routeParameters])
                : route('reports.statement.pdf', $stored),
            'DOCX' => $propertyReport
                ? route('reports.properties.word', ['asset' => $propertyId, ...$routeParameters])
                : route('reports.statement.word', $stored),
            'XLSX' => $propertyReport
                ? route('reports.properties.workbook', ['asset' => $propertyId, ...$routeParameters])
                : route('reports.statement.workbook', $stored),
        ])->map(fn (string $href, string $format): array => [
            'title' => trans(
                $propertyReport
                    ? 'app.reports.property_operating_report'
                    : 'app.reports.owner_statement',
            ),
            'subtitle' => $scope,
            'format' => $format,
            'description' => trans('app.reports.saved_report_output_'.strtolower($format)),
            'label' => trans('app.reports.saved_report_download', ['format' => $format]),
            'icon' => match ($format) {
                'PDF' => 'bi-file-earmark-pdf',
                'DOCX' => 'bi-file-earmark-word',
                default => 'bi-file-earmark-spreadsheet',
            },
            'href' => $href,
        ])->values()->map(fn (array $document, int $index): array => [
            'id' => $index + 1,
            ...$document,
        ])->all();
    }
}
