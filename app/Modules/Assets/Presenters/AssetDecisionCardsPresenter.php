<?php

namespace App\Modules\Assets\Presenters;

use App\Modules\Assets\Data\AssetDetailData;
use App\Modules\Assets\Support\AssetLeaseBalance;
use App\Modules\Assets\Support\AssetMetadata;
use App\Modules\Shared\ResourcePresenter;

class AssetDecisionCardsPresenter
{
    public function __construct(
        private readonly AssetMetadata $metadata,
        private readonly AssetLeaseBalance $balances,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function present(AssetDetailData $data): array
    {
        $asset = $data->asset;
        $lease = $data->activeLease;
        $mapReady = $this->metadata->hasPosition($asset) && $this->metadata->hasIdentity($asset);
        $mapHref = route('property-map.index', $data->actor->hasRole('superadmin')
            ? ['portfolio_id' => $asset->portfolio_id]
            : []);
        $zone = $this->resources->localized(
            $this->metadata->get($asset, 'zone_en') ?: $this->metadata->get($asset, 'zone'),
            $this->metadata->get($asset, 'zone_ar'),
        );
        $identity = collect([
            $zone ?: trans('app.assets.no_zone'),
            $this->metadata->get($asset, 'land_number') ?: trans('app.assets.no_land_number'),
            $this->metadata->coordinateLabel($asset) ?: $this->metadata->canvasPositionLabel($asset),
        ])->filter()->join(' · ');

        return [
            [
                'title' => trans('app.assets.map_readiness'),
                'value' => trans($mapReady ? 'app.assets.ready' : 'app.assets.needs_setup'),
                'detail' => $mapReady ? $identity : trans('app.assets.map_setup_help'),
                'href' => $mapReady ? $mapHref : route('assets.edit', $asset),
                'actionLabel' => trans($mapReady ? 'app.assets.open_map' : 'app.assets.fix_map_data'),
                'tone' => $mapReady ? 'teal' : 'danger',
                'icon' => 'bi-map',
            ],
            [
                'title' => trans('app.assets.rental_state'),
                'value' => trans('app.status.'.($lease->status ?? $asset->occupancy_status)),
                'detail' => $lease
                    ? trans('app.assets.tenant_balance', [
                        'tenant' => data_get($lease, 'tenantProfile.user.name', trans('app.assets.not_assigned')),
                        'balance' => $this->money($this->balances->remaining($lease), $lease->currency),
                    ])
                    : trans('app.assets.no_active_lease'),
                'href' => $lease
                    ? route('leases.show', $lease)
                    : route('leases.create', ['asset_id' => $asset->id]),
                'actionLabel' => trans($lease ? 'app.assets.open_lease' : 'app.assets.create_lease'),
                'tone' => $lease ? 'teal' : 'muted',
                'icon' => 'bi-file-earmark-text',
            ],
            [
                'title' => trans('app.assets.operations_risk'),
                'value' => $data->openMaintenanceCount,
                'detail' => trans($data->openMaintenanceCount > 0
                    ? 'app.assets.maintenance_follow_up'
                    : 'app.assets.no_maintenance_pressure'),
                'href' => route('maintenance-requests.create', ['asset_id' => $asset->id]),
                'actionLabel' => trans($data->openMaintenanceCount > 0
                    ? 'app.assets.create_follow_up'
                    : 'app.assets.log_request'),
                'tone' => $data->openMaintenanceCount > 0 ? 'danger' : 'teal',
                'icon' => 'bi-tools',
            ],
            [
                'title' => trans('app.assets.financial_position'),
                'value' => $this->money((float) $asset->valuation_amount, $asset->currency),
                'detail' => trans('app.assets.posted_expenses', [
                    'amount' => $this->money($data->postedExpenseTotal, $asset->currency),
                ]),
                'href' => route('expenses.create', ['asset_id' => $asset->id]),
                'actionLabel' => trans('app.assets.add_expense'),
                'tone' => $data->postedExpenseTotal > 0 ? 'primary' : 'muted',
                'icon' => 'bi-cash-stack',
            ],
        ];
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }
}
