<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\AssetStakeholder;

class AssetTableRowPresenter
{
    /** @return array<string, mixed> */
    public function present(Asset $asset): array
    {
        $asset->loadMissing(['portfolio', 'parent', 'currentStakeholders.user']);

        return [
            'id' => $asset->id,
            'asset_type' => $asset->asset_type,
            'usage_type' => $asset->usage_type,
            'title_en' => $asset->title_en,
            'title_ar' => $asset->title_ar,
            'code' => $asset->code,
            'status' => $asset->status,
            'occupancy_status' => $asset->occupancy_status,
            'rentable' => (bool) $asset->rentable,
            'valuation_amount' => (float) $asset->valuation_amount,
            'currency' => $asset->currency,
            'area' => $asset->area !== null ? (float) $asset->area : null,
            'level_label' => $asset->level_label,
            'unit_label' => $asset->unit_label,
            'children_count' => (int) ($asset->getAttribute('children_count') ?? 0),
            'active_leases_count' => (int) ($asset->getAttribute('active_leases_count') ?? 0),
            'is_showcase' => $asset->getIsShowcaseAttribute(),
            'parent' => $asset->parent ? [
                'id' => $asset->parent->id,
                'title_en' => $asset->parent->title_en,
                'title_ar' => $asset->parent->title_ar,
                'code' => $asset->parent->code,
            ] : null,
            'portfolio' => $asset->portfolio ? [
                'id' => $asset->portfolio->id,
                'name_en' => $asset->portfolio->name_en,
                'name_ar' => $asset->portfolio->name_ar,
            ] : null,
            'stakeholders' => $asset->currentStakeholders
                ->map(fn (AssetStakeholder $stakeholder): array => [
                    'relationship_type' => $stakeholder->relationship_type,
                    'user' => $stakeholder->user ? [
                        'id' => $stakeholder->user->id,
                        'name' => $stakeholder->user->name,
                    ] : null,
                ])
                ->values()
                ->all(),
        ];
    }
}
