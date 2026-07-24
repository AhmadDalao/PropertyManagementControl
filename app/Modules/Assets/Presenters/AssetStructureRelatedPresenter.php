<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Modules\Assets\Data\AssetDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;

final readonly class AssetStructureRelatedPresenter
{
    public function __construct(private ResourcePresenter $resources) {}

    /** @return array<int, array<string, mixed>> */
    public function present(AssetDetailData $data): array
    {
        return [
            $this->rentableAssets($data),
            $this->children($data),
        ];
    }

    /** @return array<string, mixed> */
    private function rentableAssets(AssetDetailData $data): array
    {
        $assetLabel = trans('app.assets.unit_or_space');
        $type = trans('app.assets.type');
        $occupancy = trans('app.assets.occupancy');
        $tenant = trans('app.assets.tenant');
        $open = trans('app.assets.open');
        $leasesEnabled = PortfolioModules::enabledForUser($data->actor, 'leases');
        $columns = [$assetLabel, $type, $occupancy];

        if ($leasesEnabled) {
            $columns[] = $tenant;
        }

        $columns[] = $open;

        return [
            'title' => trans('app.assets.rentable_spaces'),
            'description' => trans('app.assets.rentable_spaces_help'),
            'columns' => $columns,
            'rows' => $data->operations->rentableAssets->map(function (Asset $asset) use (
                $assetLabel,
                $type,
                $occupancy,
                $tenant,
                $open,
                $leasesEnabled,
            ): array {
                $activeLease = $asset->leases->first();

                $row = [
                    $assetLabel => $this->resources->localized($asset->title_en, $asset->title_ar),
                    $type => trans("app.assets.types.{$asset->asset_type}"),
                    $occupancy => trans("app.status.{$asset->occupancy_status}"),
                ];

                if ($leasesEnabled) {
                    $row[$tenant] = data_get($activeLease, 'tenantProfile.user.name', '-');
                }

                $row[$open] = ['label' => $open, 'href' => route('assets.show', $asset)];

                return $row;
            })->all(),
            'emptyText' => trans('app.assets.no_rentable_spaces'),
            'actionHref' => route('assets.index', [
                'property_id' => $data->operations->propertyRoot->id,
                'rentable' => 'yes',
            ]),
            'actionLabel' => trans('app.assets.open_all_spaces'),
        ];
    }

    /** @return array<string, mixed> */
    private function children(AssetDetailData $data): array
    {
        $assetLabel = trans('app.assets.asset');
        $type = trans('app.assets.type');
        $occupancy = trans('app.assets.occupancy');
        $open = trans('app.assets.open');

        return [
            'title' => trans('app.assets.child_assets'),
            'description' => trans('app.assets.child_assets_help'),
            'columns' => [$assetLabel, $type, $occupancy, $open],
            'rows' => $data->children->map(fn (Asset $asset): array => [
                $assetLabel => $this->resources->localized($asset->title_en, $asset->title_ar),
                $type => trans("app.assets.types.{$asset->asset_type}"),
                $occupancy => trans("app.status.{$asset->occupancy_status}"),
                $open => ['label' => $open, 'href' => route('assets.show', $asset)],
            ])->all(),
            'emptyText' => trans('app.assets.no_children'),
            'actionHref' => route('assets.create', ['parent_id' => $data->asset->id]),
            'actionLabel' => trans('app.assets.add_child'),
        ];
    }
}
