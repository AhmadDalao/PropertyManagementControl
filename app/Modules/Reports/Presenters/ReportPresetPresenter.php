<?php

namespace App\Modules\Reports\Presenters;

use App\Models\ReportPreset;
use App\Models\User;
use App\Modules\Reports\Support\ReportAccess;
use App\Modules\Reports\Support\ReportFilterSet;
use App\Modules\Reports\Support\ReportPeriod;
use App\Modules\Reports\Support\ReportPropertyScope;
use App\Modules\Shared\PortfolioScope;

final readonly class ReportPresetPresenter
{
    public function __construct(
        private ReportAccess $access,
        private ReportFilterSet $filters,
        private ReportPeriod $periods,
        private ReportPropertyScope $properties,
        private PortfolioScope $portfolios,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ReportPreset $preset): array
    {
        $stored = $this->filters->stored($preset->filters_json);
        $period = $this->periods->normalize($stored['period'] ?? null);
        $dates = $this->periods->resolve(
            $period,
            $stored['date_from'] ?? null,
            $stored['date_to'] ?? null,
        );

        return [
            'id' => $preset->id,
            'title_en' => $preset->title_en,
            'title_ar' => $preset->title_ar,
            'visibility' => $preset->visibility,
            'is_default' => $preset->is_default,
            'can_delete' => $this->access->canDeletePreset($actor, $preset),
            'can_edit' => $this->access->canEditPreset($actor, $preset),
            'can_duplicate' => $this->access->canViewPreset($actor, $preset),
            'period' => $period,
            'date_from' => $dates['date_from'],
            'date_to' => $dates['date_to'],
            'scope_label' => $this->scopeLabel($actor, $stored),
            'filters' => [
                'period' => $period,
                'date_from' => $dates['date_from'],
                'date_to' => $dates['date_to'],
                'portfolio_id' => $stored['portfolio_id'] ?? null,
                'property_id' => $stored['property_id'] ?? null,
            ],
            'url' => route('reports.index', $stored, false),
            'export_url' => route('reports.export', $stored, false),
            'show_url' => route('reports.saved.show', $preset, false),
            'edit_url' => route('reports.saved.edit', $preset, false),
        ];
    }

    /** @param array<string, mixed> $filters */
    private function scopeLabel(User $actor, array $filters): string
    {
        $portfolioId = $filters['portfolio_id'] ?? $actor->portfolio_id;
        $propertyId = $filters['property_id'] ?? null;

        if (is_int($propertyId)) {
            return $this->properties->label($actor, $portfolioId, $propertyId)
                ?? trans('app.reports.all_properties');
        }

        if (is_int($portfolioId)) {
            foreach ($this->portfolios->options($actor) as $portfolio) {
                if ($portfolio['id'] === $portfolioId) {
                    return $portfolio['name'];
                }
            }
        }

        return $actor->hasRole('superadmin')
            ? trans('app.reports.all_portfolios')
            : trans('app.reports.all_properties');
    }
}
