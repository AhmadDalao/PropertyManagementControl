<?php

namespace App\Modules\Assets\Presenters;

use App\Modules\Assets\Data\AssetDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;

class AssetDecisionCardsPresenter
{
    /** @return array<int, array<string, mixed>> */
    public function present(AssetDetailData $data): array
    {
        $asset = $data->asset;
        $operations = $data->operations;
        $propertyId = $operations->propertyRoot->id;

        $cards = [
            [
                'title' => trans('app.assets.occupancy_health'),
                'value' => $this->rate($operations->occupancyRate()),
                'detail' => trans('app.assets.occupancy_summary', [
                    'occupied' => $operations->occupiedCount,
                    'rentable' => $operations->rentableCount,
                    'vacant' => $operations->vacantCount,
                ]),
                'href' => route('assets.index', [
                    'property_id' => $propertyId,
                    'rentable' => 'yes',
                ]),
                'actionLabel' => trans('app.assets.open_units'),
                'tone' => $operations->occupancyRate() >= 80 ? 'teal' : 'primary',
                'icon' => 'bi-buildings',
            ],
        ];

        if (PortfolioModules::enabledForUser($data->actor, 'reports')) {
            $cards[] = [
                'title' => trans('app.assets.collection_health'),
                'value' => $this->rate($operations->collectionRate()),
                'detail' => trans('app.assets.collection_summary', [
                    'paid' => $this->money($operations->monthlyScheduledPaid, $asset->currency),
                    'due' => $this->money($operations->monthlyScheduledDue, $asset->currency),
                ]),
                'href' => route('reports.index', ['property_id' => $propertyId]),
                'actionLabel' => trans('app.assets.review_collections'),
                'tone' => $operations->monthlyScheduledDue === 0.0
                    || $operations->collectionRate() >= 90 ? 'teal' : 'primary',
                'icon' => 'bi-wallet2',
            ];
            $cards[] = [
                'title' => trans('app.assets.arrears'),
                'value' => $this->money($operations->arrears, $asset->currency),
                'detail' => trans($operations->arrears > 0
                    ? 'app.assets.arrears_follow_up'
                    : 'app.assets.no_arrears'),
                'href' => route('reports.index', ['property_id' => $propertyId]),
                'actionLabel' => trans('app.assets.open_property_report'),
                'tone' => $operations->arrears > 0 ? 'danger' : 'teal',
                'icon' => 'bi-exclamation-circle',
            ];
        }

        if (PortfolioModules::enabledForUser($data->actor, 'maintenance')) {
            $cards[] = [
                'title' => trans('app.assets.service_health'),
                'value' => $operations->openMaintenanceCount,
                'detail' => trans($operations->openMaintenanceCount > 0
                    ? 'app.assets.maintenance_follow_up'
                    : 'app.assets.no_maintenance_pressure'),
                'href' => route('maintenance-requests.index', [
                    'property_id' => $propertyId,
                ]),
                'actionLabel' => trans('app.assets.review_maintenance'),
                'tone' => $operations->openMaintenanceCount > 0 ? 'danger' : 'teal',
                'icon' => 'bi-tools',
            ];
        }

        return $cards;
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }

    private function rate(float $rate): string
    {
        return number_format($rate, 1).'%';
    }
}
