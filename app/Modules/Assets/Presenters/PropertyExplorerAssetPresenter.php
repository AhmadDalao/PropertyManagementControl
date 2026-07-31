<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use App\Modules\Shared\PortfolioScope;

final readonly class PropertyExplorerAssetPresenter
{
    public function __construct(
        private PortfolioScope $portfolios,
        private PropertyExplorerLeasePresenter $leases,
    ) {}

    /** @return array<string, mixed> */
    public function record(Asset $asset, int $rootId, bool $includeLease = true): array
    {
        $activeLease = $asset->leases->first();

        return [
            'id' => $asset->id,
            'parent_id' => $asset->parent_id,
            'title_en' => $asset->title_en,
            'title_ar' => $asset->title_ar,
            'code' => $asset->code,
            'asset_type' => $asset->asset_type,
            'usage_type' => $asset->usage_type,
            'status' => $asset->status,
            'occupancy_status' => $asset->occupancy_status,
            'rentable' => (bool) $asset->rentable,
            'children_count' => (int) ($asset->getAttribute('children_count') ?? 0),
            'parent' => $asset->parent ? [
                'id' => $asset->parent->id,
                'title_en' => $asset->parent->title_en,
                'title_ar' => $asset->parent->title_ar,
                'code' => $asset->parent->code,
            ] : null,
            'owner' => $this->stakeholder($asset, 'owner'),
            'manager' => $this->stakeholder($asset, 'manager'),
            'lease' => $includeLease ? $this->leases->present($activeLease) : null,
            'browse_href' => route('property-explorer.index', [
                'property_id' => $rootId,
                'node_id' => $asset->id,
            ]),
            'detail_href' => route('assets.show', $asset),
        ];
    }

    /** @return array<string, mixed> */
    public function selected(Asset $asset, int $rootId, bool $includeLease = true): array
    {
        return [
            ...$this->record($asset, $rootId, $includeLease),
            'valuation_amount' => (float) $asset->valuation_amount,
            'currency' => $asset->currency,
            'area' => $asset->area !== null ? (float) $asset->area : null,
            'address' => $this->portfolios->localized($asset->address, $asset->address_ar),
            'edit_href' => route('assets.edit', $asset),
            'add_child_href' => route('assets.create', [
                'portfolio_id' => $asset->portfolio_id,
                'parent_id' => $asset->id,
            ]),
            'create_lease_href' => route('leases.create', [
                'portfolio_id' => $asset->portfolio_id,
                'asset_id' => $asset->id,
            ]),
            'maintenance_href' => route('maintenance-requests.index', [
                'property_id' => $rootId,
                'search' => $asset->code,
            ]),
        ];
    }

    /** @return array{id:int,name:string}|null */
    private function stakeholder(Asset $asset, string $relationship): ?array
    {
        $stakeholder = $asset->currentStakeholders->first(
            fn (AssetStakeholder $item): bool => $item->relationship_type === $relationship
                && (bool) $item->is_primary,
        );

        return $stakeholder?->user ? [
            'id' => $stakeholder->user->id,
            'name' => $stakeholder->user->name,
        ] : null;
    }
}
