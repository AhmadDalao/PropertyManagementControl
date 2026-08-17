<?php

namespace App\Modules\Reports\Presenters;

use App\Models\ReportPreset;

final class ReportPresetDetailActionPresenter
{
    /**
     * @param  array<string, mixed>  $view
     * @return array<int, array<string, mixed>>
     */
    public function present(ReportPreset $preset, array $view): array
    {
        return array_values(array_filter([
            [
                'label' => trans('app.reports.run_saved_report'),
                'href' => $view['url'],
                'variant' => 'primary',
            ],
            $view['can_duplicate'] ? [
                'label' => trans('app.reports.duplicate'),
                'href' => route('reports.saved.duplicate', $preset),
                'method' => 'post',
                'variant' => 'secondary',
            ] : null,
            $view['can_delete'] ? [
                'label' => trans('app.reports.remove'),
                'href' => route('reports.saved.destroy', $preset),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.reports.remove_saved_confirm'),
            ] : null,
        ]));
    }
}
