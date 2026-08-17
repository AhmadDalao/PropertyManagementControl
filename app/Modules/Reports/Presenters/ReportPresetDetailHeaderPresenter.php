<?php

namespace App\Modules\Reports\Presenters;

use App\Modules\Reports\Data\ReportPresetDetailData;

final class ReportPresetDetailHeaderPresenter
{
    /** @return array<string, mixed> */
    public function present(ReportPresetDetailData $data): array
    {
        return [
            'eyebrow' => trans('app.reports.saved_report_detail_eyebrow'),
            'title' => $data->title,
            'description' => trans('app.reports.saved_report_detail_description', [
                'scope' => $data->view['scope_label'],
            ]),
            'backHref' => route('reports.saved.index'),
            'backLabel' => trans('app.reports.saved_reports_title'),
            'actions' => $data->view['can_edit'] ? [[
                'label' => trans('app.reports.edit_saved_report'),
                'href' => $data->view['edit_url'],
                'variant' => 'primary',
            ]] : [],
        ];
    }
}
