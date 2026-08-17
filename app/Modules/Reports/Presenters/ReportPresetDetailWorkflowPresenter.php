<?php

namespace App\Modules\Reports\Presenters;

use App\Modules\Reports\Data\ReportPresetDetailData;

final readonly class ReportPresetDetailWorkflowPresenter
{
    public function __construct(private ReportPresetDetailActionPresenter $actions) {}

    /** @return array<string, mixed> */
    public function present(ReportPresetDetailData $data): array
    {
        return [
            'eyebrow' => trans('app.resource.next_step'),
            'title' => trans('app.reports.saved_report_workflow_title'),
            'description' => trans('app.reports.saved_report_workflow_description'),
            'status' => $data->visibility,
            'tone' => 'primary',
            'icon' => 'bi-bar-chart-line',
            'actions' => $this->actions->present($data->preset, $data->view),
        ];
    }
}
