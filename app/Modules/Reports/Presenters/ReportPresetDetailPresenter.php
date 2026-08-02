<?php

namespace App\Modules\Reports\Presenters;

use App\Models\ReportPreset;
use App\Models\User;
use App\Modules\Reports\Queries\ReportPresetQuery;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;
use Carbon\CarbonImmutable;

final readonly class ReportPresetDetailPresenter
{
    public function __construct(
        private ReportPresetQuery $presets,
        private ReportPresetPresenter $presenter,
        private ReportPresetDetailActionPresenter $actions,
        private ReportPresetOutputPresenter $outputs,
        private ResourcePresenter $resources,
        private UserAccess $users,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ReportPreset $preset): array
    {
        $this->presets->ensureAccessible($actor, $preset);
        $preset->loadMissing(['user', 'portfolio']);
        $view = $this->presenter->present($actor, $preset);
        $title = $this->resources->localized($preset->title_en, $preset->title_ar)
            ?? trans('app.reports.saved_reports_title');
        $period = trans('app.reports.period_'.$view['period']);
        $dateRange = $this->date($view['date_from']).' – '.$this->date($view['date_to']);
        $visibility = trans('app.reports.visibility_'.$preset->visibility);

        return [
            'header' => [
                'eyebrow' => trans('app.reports.saved_report_detail_eyebrow'),
                'title' => $title,
                'description' => trans('app.reports.saved_report_detail_description', [
                    'scope' => $view['scope_label'],
                ]),
                'backHref' => route('reports.saved.index'),
                'backLabel' => trans('app.reports.saved_reports_title'),
                'actions' => $this->actions->present($preset, $view),
            ],
            'decisionCards' => [
                [
                    'title' => trans('app.reports.report_period'),
                    'value' => $period,
                    'detail' => $dateRange,
                    'tone' => 'primary',
                    'icon' => 'bi-calendar3',
                ],
                [
                    'title' => trans('app.reports.report_scope'),
                    'value' => $view['scope_label'],
                    'detail' => $visibility,
                    'tone' => 'teal',
                    'icon' => 'bi-buildings',
                ],
                [
                    'title' => trans('app.reports.available_outputs'),
                    'value' => 'PDF · DOCX · XLSX',
                    'detail' => trans('app.reports.available_outputs_help'),
                    'tone' => 'primary',
                    'icon' => 'bi-file-earmark-arrow-down',
                ],
                [
                    'title' => trans('app.reports.report_owner'),
                    'value' => $preset->user?->name ?: trans('app.resource.system'),
                    'detail' => trans('app.reports.updated_on', [
                        'date' => $this->dateTime($preset->updated_at?->toDateTimeString()),
                    ]),
                    'tone' => 'muted',
                    'icon' => 'bi-person-check',
                ],
            ],
            'sections' => [
                [
                    'title' => trans('app.reports.saved_configuration'),
                    'description' => trans('app.reports.saved_configuration_help'),
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.reports.preset_name_en'), 'value' => $preset->title_en],
                        ['label' => trans('app.reports.preset_name_ar'), 'value' => $preset->title_ar],
                        ['label' => trans('app.reports.report_period'), 'value' => $period],
                        ['label' => trans('app.reports.date_window'), 'value' => $dateRange],
                        ['label' => trans('app.reports.report_scope'), 'value' => $view['scope_label']],
                    ]),
                ],
                [
                    'title' => trans('app.reports.access_ownership'),
                    'description' => trans('app.reports.access_ownership_help'),
                    'items' => $this->resources->detailItems([
                        [
                            'label' => trans('app.reports.report_owner'),
                            'value' => $preset->user?->name,
                            'href' => $this->users->recordHref($actor, $preset->user),
                        ],
                        ['label' => trans('app.reports.preset_visibility'), 'value' => $visibility],
                        [
                            'label' => trans('app.reports.default_view'),
                            'value' => trans($preset->is_default ? 'app.common.yes' : 'app.common.no'),
                        ],
                        [
                            'label' => trans('app.reports.portfolio_scope'),
                            'value' => $this->resources->localized(
                                $preset->portfolio?->name_en,
                                $preset->portfolio?->name_ar,
                            ) ?? ($preset->visibility === 'global'
                                ? ($actor->hasRole('superadmin')
                                    ? trans('app.reports.all_portfolios')
                                    : $view['scope_label'])
                                : null),
                        ],
                        [
                            'label' => trans('app.reports.created_on'),
                            'value' => $this->dateTime($preset->created_at?->toDateTimeString()),
                        ],
                        [
                            'label' => trans('app.reports.last_updated'),
                            'value' => $this->dateTime($preset->updated_at?->toDateTimeString()),
                        ],
                    ]),
                ],
            ],
            'documents' => $this->outputs->present($preset, $view, $dateRange),
            'timeline' => $this->resources->activityTimeline($preset),
        ];
    }

    private function date(string $date): string
    {
        $localized = CarbonImmutable::parse($date)->locale(app()->getLocale());

        return $localized instanceof CarbonImmutable
            ? $localized->translatedFormat('j M Y')
            : $date;
    }

    private function dateTime(?string $date): string
    {
        if (! $date) {
            return '-';
        }

        $localized = CarbonImmutable::parse($date)->locale(app()->getLocale());

        return $localized instanceof CarbonImmutable
            ? $localized->translatedFormat('j M Y, H:i')
            : $date;
    }
}
