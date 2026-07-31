<?php

namespace App\Modules\Notifications\Queries;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Assets\Support\AssetHierarchy;
use Illuminate\Support\Collection;

final readonly class MaintenanceNotificationRecipientsQuery
{
    public function __construct(private AssetHierarchy $hierarchy) {}

    /** @return Collection<int, User> */
    public function management(MaintenanceRequest $request, User $actor): Collection
    {
        $request->loadMissing(['asset', 'portfolio']);
        $root = $request->asset ? $this->hierarchy->root($request->asset) : null;
        $managerIds = $root?->currentStakeholders()
            ->where('relationship_type', 'manager')
            ->pluck('user_id')
            ->all() ?? [];
        $operatorIds = User::query()
            ->where('portfolio_id', $request->portfolio_id)
            ->where('status', 'active')
            ->where(function ($users) use ($request, $managerIds): void {
                $users->whereHas('roles', fn ($roles) => $roles->where('name', 'owner'));

                if ($request->portfolio?->owner_user_id) {
                    $users->orWhere('id', $request->portfolio->owner_user_id);
                }

                if ($request->assigned_to_user_id) {
                    $users->orWhere('id', $request->assigned_to_user_id);
                }

                if ($managerIds !== []) {
                    $users->orWhereIn('id', $managerIds);
                }

                if ($managerIds === [] && $request->assigned_to_user_id === null) {
                    $users->orWhereHas(
                        'roles',
                        fn ($roles) => $roles->where('name', 'property_manager'),
                    );
                }
            })
            ->pluck('id');
        $superadminIds = User::query()
            ->where('status', 'active')
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'superadmin'))
            ->pluck('id');

        return User::query()
            ->whereIn('id', $operatorIds->merge($superadminIds)->unique())
            ->where('id', '!=', $actor->id)
            ->get();
    }

    /** @return Collection<int, User> */
    public function tenant(MaintenanceRequest $request, User $actor): Collection
    {
        $request->loadMissing('tenantProfile.user');
        $tenant = $request->tenantProfile?->user;

        return $tenant && $tenant->status === 'active' && $tenant->id !== $actor->id
            ? collect([$tenant])
            : collect();
    }
}
