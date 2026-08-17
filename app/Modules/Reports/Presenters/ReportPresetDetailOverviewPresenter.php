<?php

namespace App\Modules\Reports\Presenters;

use App\Modules\Reports\Data\ReportPresetDetailData;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

final readonly class ReportPresetDetailOverviewPresenter
{
    public function __construct(
        private ResourcePresenter $resources,
        private UserAccess $users,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function stats(ReportPresetDetailData $data): array
    {
        return [
            $this->stat('report_period', $data->period, 'primary'),
            $this->stat('report_scope', $data->view['scope_label'], 'teal'),
            $this->stat('preset_visibility', $data->visibility, 'muted'),
            $this->stat('available_outputs', 'PDF · DOCX · XLSX', 'primary'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(ReportPresetDetailData $data): array
    {
        $preset = $data->preset;

        return [
            $this->section('identity', 'saved_configuration', 'saved_configuration_help', [
                ['label' => trans('app.reports.preset_name_en'), 'value' => $preset->title_en],
                ['label' => trans('app.reports.preset_name_ar'), 'value' => $preset->title_ar],
            ]),
            $this->section('scope', 'report_scope_period', 'report_scope_period_help', [
                ['label' => trans('app.reports.report_period'), 'value' => $data->period],
                ['label' => trans('app.reports.date_window'), 'value' => $data->dateRange],
                ['label' => trans('app.reports.report_scope'), 'value' => $data->view['scope_label']],
                ['label' => trans('app.reports.portfolio_scope'), 'value' => $data->portfolioScope],
            ]),
            $this->section('access', 'access_ownership', 'access_ownership_help', [
                [
                    'label' => trans('app.reports.report_owner'),
                    'value' => $preset->user?->name,
                    'href' => $this->users->recordHref($data->actor, $preset->user),
                ],
                ['label' => trans('app.reports.preset_visibility'), 'value' => $data->visibility],
                [
                    'label' => trans('app.reports.default_view'),
                    'value' => trans($preset->is_default ? 'app.common.yes' : 'app.common.no'),
                ],
                ['label' => trans('app.reports.created_on'), 'value' => $data->createdAt],
                ['label' => trans('app.reports.last_updated'), 'value' => $data->updatedAt],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private function stat(string $key, string $value, string $tone): array
    {
        return [
            'label' => trans("app.reports.{$key}"),
            'value' => $value,
            'tone' => $tone,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function section(string $key, string $title, string $description, array $items): array
    {
        return [
            'key' => $key,
            'title' => trans("app.reports.{$title}"),
            'description' => trans("app.reports.{$description}"),
            'items' => $this->resources->detailItems($items),
        ];
    }
}
