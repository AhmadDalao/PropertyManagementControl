<?php

namespace App\Modules\Assets\Presenters;

use App\Modules\Assets\Data\AssetDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;

final class AssetWorkflowPresenter
{
    public function __construct(
        private readonly AssetCurrencySummaryPresenter $currencies,
    ) {}

    /** @return array<string, mixed> */
    public function present(AssetDetailData $data): array
    {
        $operations = $data->operations;
        $propertyId = $operations->propertyRoot->id;

        if (
            $operations->hasArrears()
            && PortfolioModules::enabledForUser($data->actor, 'reports')
        ) {
            return [
                'eyebrow' => trans('app.assets.next_owner_action'),
                'title' => trans('app.assets.collect_overdue_rent'),
                'description' => trans('app.assets.collect_overdue_rent_help'),
                'status' => $this->currencies->money($operations, 'arrears'),
                'tone' => 'danger',
                'icon' => 'bi-exclamation-triangle',
                'actions' => [
                    $this->action(
                        trans('app.assets.review_collections'),
                        route('reports.properties.show', $propertyId),
                        'primary',
                    ),
                ],
            ];
        }

        if (
            $operations->openMaintenanceCount > 0
            && PortfolioModules::enabledForUser($data->actor, 'maintenance')
        ) {
            return [
                'eyebrow' => trans('app.assets.next_owner_action'),
                'title' => trans('app.assets.resolve_open_service'),
                'description' => trans('app.assets.resolve_open_service_help'),
                'status' => trans('app.assets.open_request_count', [
                    'count' => $operations->openMaintenanceCount,
                ]),
                'tone' => 'danger',
                'icon' => 'bi-tools',
                'actions' => [
                    $this->action(
                        trans('app.assets.review_maintenance'),
                        route('maintenance-requests.index', [
                            'property_id' => $propertyId,
                            'status' => 'open',
                        ]),
                        'primary',
                    ),
                ],
            ];
        }

        if ($operations->vacantCount > 0) {
            return [
                'eyebrow' => trans('app.assets.next_owner_action'),
                'title' => trans('app.assets.fill_vacant_units'),
                'description' => trans('app.assets.fill_vacant_units_help'),
                'status' => trans('app.assets.vacant_unit_count', [
                    'count' => $operations->vacantCount,
                ]),
                'tone' => 'primary',
                'icon' => 'bi-building-check',
                'actions' => [
                    $this->action(
                        trans('app.assets.review_vacancies'),
                        route('assets.index', [
                            'property_id' => $propertyId,
                            'rentable' => 'yes',
                            'occupancy_status' => 'vacant',
                        ]),
                        'primary',
                    ),
                ],
            ];
        }

        $reportsEnabled = PortfolioModules::enabledForUser($data->actor, 'reports');

        return [
            'eyebrow' => trans('app.assets.next_owner_action'),
            'title' => trans('app.assets.property_on_track'),
            'description' => trans('app.assets.property_on_track_help'),
            'status' => trans('app.assets.no_immediate_risk'),
            'tone' => 'teal',
            'icon' => 'bi-check2-circle',
            'actions' => [
                $this->action(
                    trans($reportsEnabled
                        ? 'app.assets.open_property_report'
                        : 'app.assets.open_all_spaces'),
                    $reportsEnabled
                        ? route('reports.properties.show', $propertyId)
                        : route('assets.index', ['property_id' => $propertyId]),
                    'secondary',
                ),
            ],
        ];
    }

    /** @return array{label:string,href:string,variant:string} */
    private function action(string $label, string $href, string $variant): array
    {
        return compact('label', 'href', 'variant');
    }
}
