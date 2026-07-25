<?php

namespace App\Modules\Users\Queries;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use App\Models\User;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Database\Eloquent\Builder;

final class ManagerPropertyAssignmentQuery
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function get(User $manager, User $actor): array
    {
        $this->ensureAccess($manager, $actor);
        $manager->loadMissing('portfolio');
        $properties = Asset::query()
            ->where('portfolio_id', $manager->portfolio_id)
            ->where('status', '!=', 'archived')
            ->where(function (Builder $assets) use ($manager): void {
                $assets
                    ->whereIn('asset_type', ['property', 'building'])
                    ->orWhereHas('currentStakeholders', fn (Builder $stakeholders) => $stakeholders
                        ->where('relationship_type', 'manager')
                        ->where('user_id', $manager->id));
            })
            ->with([
                'parent:id,title_en,title_ar,code',
                'currentStakeholders' => fn ($stakeholders) => $stakeholders
                    ->where('relationship_type', 'manager')
                    ->where('is_primary', true)
                    ->with('user:id,name'),
            ])
            ->withCount('children')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy(app()->isLocale('ar') ? 'title_ar' : 'title_en')
            ->limit(250)
            ->get();
        $selectedIds = AssetStakeholder::query()
            ->where('portfolio_id', $manager->portfolio_id)
            ->where('user_id', $manager->id)
            ->where('relationship_type', 'manager')
            ->whereNull('ends_on')
            ->whereIn('asset_id', $properties->pluck('id'))
            ->pluck('asset_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return [
            'manager' => [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'portfolio' => $this->resources->localized(
                    $manager->portfolio?->name_en,
                    $manager->portfolio?->name_ar,
                ),
            ],
            'properties' => $properties->map(function (Asset $asset) use ($selectedIds): array {
                $currentManager = $asset->currentStakeholders->first();

                return [
                    'id' => $asset->id,
                    'title' => $this->resources->localized($asset->title_en, $asset->title_ar) ?? $asset->code,
                    'code' => $asset->code,
                    'asset_type' => $asset->asset_type,
                    'usage_type' => $asset->usage_type,
                    'status' => $asset->status,
                    'parent' => $asset->parent
                        ? $this->resources->localized($asset->parent->title_en, $asset->parent->title_ar)
                        : null,
                    'children_count' => (int) $asset->children_count,
                    'selected' => in_array($asset->id, $selectedIds, true),
                    'current_manager' => $currentManager?->user ? [
                        'id' => $currentManager->user->id,
                        'name' => $currentManager->user->name,
                    ] : null,
                ];
            })->all(),
            'selected_ids' => $selectedIds,
            'action' => route('users.property-assignments.update', $manager),
            'back_href' => route('users.show', $manager),
        ];
    }

    private function ensureAccess(User $manager, User $actor): void
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
