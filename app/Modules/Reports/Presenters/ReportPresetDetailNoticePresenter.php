<?php

namespace App\Modules\Reports\Presenters;

use App\Modules\Reports\Data\ReportPresetDetailData;

final class ReportPresetDetailNoticePresenter
{
    /** @return array<string, array<string, string>> */
    public function present(ReportPresetDetailData $data): array
    {
        $rolling = $data->view['period'] !== 'custom';

        return [
            'scope' => $this->notice(
                $rolling ? 'primary' : 'teal',
                'bi-calendar3',
                trans($rolling
                    ? 'app.reports.saved_report_rolling_title'
                    : 'app.reports.saved_report_fixed_title'),
                trans($rolling
                    ? 'app.reports.rolling_period_help'
                    : 'app.reports.saved_report_fixed_description'),
            ),
            'outputs' => $this->notice(
                'primary',
                'bi-file-earmark-arrow-down',
                trans('app.reports.saved_report_outputs_title'),
                trans('app.reports.saved_report_outputs_description'),
            ),
            'access' => $this->notice(
                $data->preset->visibility === 'private' ? 'muted' : 'teal',
                'bi-shield-check',
                trans('app.reports.saved_report_access_title'),
                trans('app.reports.saved_report_access_'.$data->preset->visibility),
            ),
        ];
    }

    /** @return array<string, string> */
    private function notice(string $tone, string $icon, string $title, string $description): array
    {
        return compact('tone', 'icon', 'title', 'description');
    }
}
