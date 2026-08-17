<?php

namespace App\Modules\Reports\Queries;

use App\Models\ReportPreset;
use App\Models\User;
use App\Modules\Reports\Data\ReportPresetDetailData;
use App\Modules\Reports\Presenters\ReportPresetPresenter;
use App\Modules\Shared\ResourcePresenter;
use Carbon\CarbonImmutable;

final readonly class ReportPresetDetailQuery
{
    public function __construct(
        private ReportPresetQuery $presets,
        private ReportPresetPresenter $presenter,
        private ResourcePresenter $resources,
    ) {}

    public function get(User $actor, ReportPreset $preset): ReportPresetDetailData
    {
        $this->presets->ensureAccessible($actor, $preset);
        $preset->loadMissing(['user', 'portfolio']);
        $view = $this->presenter->present($actor, $preset);

        return new ReportPresetDetailData(
            preset: $preset,
            actor: $actor,
            view: $view,
            title: $this->resources->localized($preset->title_en, $preset->title_ar)
                ?? trans('app.reports.saved_reports_title'),
            period: trans('app.reports.period_'.$view['period']),
            dateRange: $this->date($view['date_from']).' – '.$this->date($view['date_to']),
            visibility: trans('app.reports.visibility_'.$preset->visibility),
            portfolioScope: $this->resources->localized(
                $preset->portfolio?->name_en,
                $preset->portfolio?->name_ar,
            ) ?? $view['scope_label'],
            createdAt: $this->dateTime($preset->created_at?->toDateTimeString()),
            updatedAt: $this->dateTime($preset->updated_at?->toDateTimeString()),
        );
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
