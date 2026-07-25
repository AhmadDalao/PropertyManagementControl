<?php

namespace App\Modules\Users\Actions;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use App\Models\User;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SyncManagerPropertyAssignments
{
    public function __construct(private readonly UserAccess $access) {}

    /** @param array<int, mixed> $assetIds */
    public function handle(User $actor, User $manager, array $assetIds): void
    {
        $this->ensureAccess($actor, $manager);
        $selectedIds = collect($assetIds)->map(fn (mixed $id): int => (int) $id)->unique()->values();
        $assets = Asset::query()
            ->where('portfolio_id', $manager->portfolio_id)
            ->where('status', '!=', 'archived')
            ->where(function ($assets) use ($manager): void {
                $assets
                    ->whereIn('asset_type', ['property', 'building'])
                    ->orWhereHas('currentStakeholders', fn ($stakeholders) => $stakeholders
                        ->where('relationship_type', 'manager')
                        ->where('user_id', $manager->id));
            })
            ->whereIn('id', $selectedIds)
            ->get();

        abort_unless(
            $assets->count() === $selectedIds->count(),
            422,
            trans('app.errors.manager_property_selection_invalid'),
        );

        DB::transaction(function () use ($manager, $assets, $selectedIds): void {
            AssetStakeholder::query()
                ->where('portfolio_id', $manager->portfolio_id)
                ->where('user_id', $manager->id)
                ->where('relationship_type', 'manager')
                ->whereNull('ends_on')
                ->whereNotIn('asset_id', $selectedIds)
                ->lockForUpdate()
                ->get()
                ->each(fn (AssetStakeholder $stakeholder) => $stakeholder->update([
                    'ends_on' => now()->toDateString(),
                ]));

            $assets->each(fn (Asset $asset) => $this->assign($asset, $manager));
        }, attempts: 3);
    }

    private function assign(Asset $asset, User $manager): void
    {
        /** @var Collection<int, AssetStakeholder> $active */
        $active = $asset->stakeholders()
            ->where('relationship_type', 'manager')
            ->whereNull('ends_on')
            ->lockForUpdate()
            ->orderBy('id')
            ->get();
        $kept = $active->firstWhere('user_id', $manager->id);

        $active
            ->reject(fn (AssetStakeholder $stakeholder): bool => $kept?->id === $stakeholder->id)
            ->each(fn (AssetStakeholder $stakeholder) => $stakeholder->update([
                'ends_on' => now()->toDateString(),
            ]));

        if ($kept) {
            $kept->update(['is_primary' => true]);

            return;
        }

        $asset->stakeholders()->create([
            'portfolio_id' => $asset->portfolio_id,
            'user_id' => $manager->id,
            'relationship_type' => 'manager',
            'is_primary' => true,
            'starts_on' => now()->toDateString(),
        ]);
    }

    private function ensureAccess(User $actor, User $manager): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner'])
                && $manager->hasRole('property_manager')
                && $this->access->canManage($actor, $manager),
            403,
            trans('app.errors.manage_account_denied'),
        );
    }
}
