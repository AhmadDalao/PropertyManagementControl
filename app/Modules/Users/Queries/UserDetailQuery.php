<?php

namespace App\Modules\Users\Queries;

use App\Models\User;
use App\Modules\Users\Data\UserDetailData;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Database\Eloquent\Builder;

final class UserDetailQuery
{
    public function __construct(private readonly UserAccess $access) {}

    public function get(User $target, User $actor): UserDetailData
    {
        $this->access->ensureCanManage($actor, $target);
        $user = User::query()
            ->with([
                'portfolio',
                'roles',
                'tenantProfile' => fn ($profile) => $profile->withCount([
                    'leases as active_leases_count' => fn (Builder $leases) => $leases->where('status', 'active'),
                ]),
            ])
            ->withCount([
                'portfoliosOwned',
                'recordedPayments',
                'assetStakeholders as current_asset_assignments_count' => fn (Builder $stakeholders) => $stakeholders
                    ->whereNull('ends_on'),
                'assignedMaintenanceRequests as open_assignments_count' => fn (Builder $requests) => $requests
                    ->whereIn('status', ['open', 'in_progress']),
            ])
            ->whereKey($target->id)
            ->firstOrFail();
        $this->access->ensureCanManage($actor, $user);

        return new UserDetailData(
            user: $user,
            stakeholders: $user->assetStakeholders()
                ->with('asset:id,portfolio_id,title_en,title_ar,code,status')
                ->latest()
                ->limit(8)
                ->get(),
            maintenance: $user->assignedMaintenanceRequests()
                ->with('asset:id,portfolio_id,title_en,title_ar,code')
                ->latest('requested_at')
                ->limit(8)
                ->get(),
            documents: $user->uploadedDocuments()->latest()->limit(8)->get(),
        );
    }
}
