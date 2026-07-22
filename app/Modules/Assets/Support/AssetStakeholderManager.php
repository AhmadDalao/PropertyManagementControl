<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use Illuminate\Support\Collection;

class AssetStakeholderManager
{
    public function sync(Asset $asset, ?int $ownerId, ?int $managerId): void
    {
        foreach (['owner' => $ownerId, 'manager' => $managerId] as $relationship => $userId) {
            $this->syncRelationship($asset, $relationship, $userId);
        }
    }

    private function syncRelationship(Asset $asset, string $relationship, ?int $userId): void
    {
        /** @var Collection<int, AssetStakeholder> $active */
        $active = $asset->stakeholders()
            ->where('relationship_type', $relationship)
            ->where('is_primary', true)
            ->whereNull('ends_on')
            ->lockForUpdate()
            ->orderBy('id')
            ->get();
        $kept = $userId ? $active->firstWhere('user_id', $userId) : null;

        $active
            ->reject(fn (AssetStakeholder $stakeholder): bool => $kept?->id === $stakeholder->id)
            ->each(fn (AssetStakeholder $stakeholder) => $stakeholder->update([
                'ends_on' => now()->toDateString(),
            ]));

        if ($userId && ! $kept) {
            $asset->stakeholders()->create([
                'portfolio_id' => $asset->portfolio_id,
                'user_id' => $userId,
                'relationship_type' => $relationship,
                'is_primary' => true,
                'starts_on' => now()->toDateString(),
            ]);
        }
    }
}
