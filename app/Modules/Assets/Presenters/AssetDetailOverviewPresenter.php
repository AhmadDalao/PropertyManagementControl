<?php

namespace App\Modules\Assets\Presenters;

use App\Modules\Assets\Data\AssetDetailData;
use App\Modules\Assets\Support\AssetLeaseBalance;
use App\Modules\Assets\Support\AssetMetadata;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

class AssetDetailOverviewPresenter
{
    public function __construct(
        private readonly AssetMetadata $metadata,
        private readonly AssetLeaseBalance $balances,
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $users,
    ) {}

    /** @return array<string, mixed> */
    public function present(AssetDetailData $data): array
    {
        $asset = $data->asset;
        $lease = $data->activeLease;
        $title = $this->resources->localized($asset->title_en, $asset->title_ar);
        $portfolio = $this->resources->localized($asset->portfolio?->name_en, $asset->portfolio?->name_ar);
        $parent = $this->resources->localized($asset->parent?->title_en, $asset->parent?->title_ar);
        $address = $this->resources->localized($asset->address, $asset->address_ar);
        $zone = $this->resources->localized(
            $this->metadata->get($asset, 'zone_en') ?: $this->metadata->get($asset, 'zone'),
            $this->metadata->get($asset, 'zone_ar'),
        );
        $owner = $asset->currentStakeholders->firstWhere('relationship_type', 'owner');
        $manager = $asset->currentStakeholders->firstWhere('relationship_type', 'manager');
        $mapHref = route('property-map.index', $data->actor->hasRole('superadmin')
            ? ['portfolio_id' => $asset->portfolio_id]
            : []);

        return [
            'header' => [
                'eyebrow' => trans('app.assets.detail_eyebrow'),
                'title' => $title,
                'description' => implode(' · ', [
                    $asset->code,
                    trans("app.assets.types.{$asset->asset_type}"),
                    trans("app.assets.usages.{$asset->usage_type}"),
                ]),
                'backHref' => route('assets.index'),
                'backLabel' => trans('app.assets.all_assets'),
                'actions' => [
                    ['label' => trans('app.assets.edit_asset_action'), 'href' => route('assets.edit', $asset), 'variant' => 'primary'],
                    ['label' => trans('app.assets.create_child'), 'href' => route('assets.create', ['parent_id' => $asset->id]), 'variant' => 'secondary'],
                    ['label' => trans('app.assets.create_lease'), 'href' => route('leases.create', ['asset_id' => $asset->id]), 'variant' => 'secondary'],
                ],
            ],
            'spotlight' => [
                'eyebrow' => trans('app.assets.clicked_land_record'),
                'title' => $this->metadata->get($asset, 'land_number') ?: $asset->code,
                'subtitle' => $zone ?: trans('app.assets.no_zone_recorded'),
                'description' => $address ?: trans('app.assets.no_address_recorded'),
                'status' => trans("app.status.{$asset->occupancy_status}"),
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.assets.property'), 'value' => $title],
                    ['label' => trans('app.assets.portfolio'), 'value' => $portfolio],
                    ['label' => trans('app.assets.owner'), 'value' => data_get($owner, 'user.name', trans('app.assets.not_assigned'))],
                    ['label' => trans('app.assets.manager'), 'value' => data_get($manager, 'user.name', trans('app.assets.not_assigned'))],
                    ['label' => trans('app.assets.coordinates'), 'value' => $this->metadata->coordinateLabel($asset)],
                    ['label' => trans('app.assets.map_position'), 'value' => $this->metadata->canvasPositionLabel($asset)],
                ]),
                'actions' => [
                    ['label' => trans('app.assets.back_to_map'), 'href' => $mapHref, 'variant' => 'light'],
                    ['label' => trans('app.assets.edit_map_data'), 'href' => route('assets.edit', $asset), 'variant' => 'primary'],
                ],
            ],
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.assets.valuation'), 'value' => $this->money((float) $asset->valuation_amount, $asset->currency), 'tone' => 'primary'],
                ['label' => trans('app.assets.occupancy'), 'value' => trans("app.status.{$asset->occupancy_status}"), 'tone' => $asset->occupancy_status === 'occupied' ? 'teal' : 'muted'],
                ['label' => trans('app.assets.children'), 'value' => $data->childrenCount],
                ['label' => trans('app.assets.lease_records'), 'value' => $data->leaseCount, 'tone' => $lease ? 'teal' : 'muted'],
                ['label' => trans('app.assets.open_maintenance'), 'value' => $data->openMaintenanceCount, 'tone' => 'danger'],
                ['label' => trans('app.assets.posted_expenses_label'), 'value' => $this->money($data->postedExpenseTotal, $asset->currency), 'tone' => $data->postedExpenseTotal > 0 ? 'primary' : 'muted'],
            ]),
            'sections' => [
                [
                    'title' => trans('app.assets.profile_section'),
                    'description' => trans('app.assets.profile_section_help'),
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.assets.title_ar'), 'value' => $asset->title_ar],
                        ['label' => trans('app.assets.code'), 'value' => $asset->code],
                        ['label' => trans('app.assets.portfolio'), 'value' => $portfolio, 'href' => $asset->portfolio ? route('portfolios.show', $asset->portfolio) : null],
                        ['label' => trans('app.assets.parent_asset'), 'value' => $parent, 'href' => $asset->parent ? route('assets.show', $asset->parent) : null],
                        ['label' => trans('app.assets.rentable'), 'value' => trans($asset->rentable ? 'app.assets.yes' : 'app.assets.no')],
                        ['label' => trans('app.assets.status'), 'value' => trans("app.status.{$asset->status}")],
                        ['label' => trans('app.assets.area'), 'value' => $asset->area ? trans('app.assets.area_sqm', ['area' => $asset->area]) : null],
                        ['label' => trans('app.assets.address'), 'value' => $address],
                    ]),
                ],
                [
                    'title' => trans('app.assets.map_land_section'),
                    'description' => trans('app.assets.map_land_section_help'),
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.assets.zone'), 'value' => $zone],
                        ['label' => trans('app.assets.land_number'), 'value' => $this->metadata->get($asset, 'land_number')],
                        ['label' => trans('app.assets.latitude'), 'value' => $this->metadata->get($asset, 'latitude')],
                        ['label' => trans('app.assets.longitude'), 'value' => $this->metadata->get($asset, 'longitude')],
                        ['label' => trans('app.assets.map_position'), 'value' => $this->metadata->canvasPositionLabel($asset)],
                    ]),
                ],
                [
                    'title' => trans('app.assets.ownership_section'),
                    'description' => trans('app.assets.ownership_section_help'),
                    'items' => $asset->currentStakeholders->map(fn ($stakeholder): array => [
                        'label' => trans("app.assets.relationships.{$stakeholder->relationship_type}"),
                        'value' => $stakeholder->user?->name,
                        'href' => $this->users->recordHref($data->actor, $stakeholder->user),
                        'tone' => $stakeholder->is_primary ? 'primary' : 'muted',
                    ])->values()->all(),
                ],
                [
                    'title' => trans('app.assets.active_rental_section'),
                    'description' => trans('app.assets.active_rental_section_help'),
                    'tab' => 'financial',
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.assets.lease'), 'value' => $lease?->code, 'href' => $lease ? route('leases.show', $lease) : null],
                        ['label' => trans('app.assets.tenant'), 'value' => data_get($lease, 'tenantProfile.user.name'), 'href' => $lease?->tenantProfile ? route('tenants.show', $lease->tenantProfile) : null],
                        ['label' => trans('app.assets.balance'), 'value' => $lease ? $this->money($this->balances->remaining($lease), $lease->currency) : null],
                    ]),
                ],
            ],
        ];
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }
}
