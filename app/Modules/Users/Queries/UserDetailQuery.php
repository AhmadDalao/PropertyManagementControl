<?php

namespace App\Modules\Users\Queries;

use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Users\Data\UserDetailData;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Database\Eloquent\Builder;

final class UserDetailQuery
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly AssignedPropertyScope $assignments,
    ) {}

    public function get(User $target, User $actor): UserDetailData
    {
        $this->access->ensureCanManage($actor, $target);
        $assetsEnabled = PortfolioModules::enabledForUser($actor, 'assets');
        $documentsEnabled = PortfolioModules::enabledForUser($actor, 'documents');
        $leasesEnabled = PortfolioModules::enabledForUser($actor, 'leases');
        $maintenanceEnabled = PortfolioModules::enabledForUser($actor, 'maintenance');
        $paymentsEnabled = PortfolioModules::enabledForUser($actor, 'payments');
        $tenantsEnabled = PortfolioModules::enabledForUser($actor, 'tenants');
        $relations = ['portfolio', 'roles'];
        $counts = ['portfoliosOwned'];

        if ($tenantsEnabled) {
            $relations[] = 'tenantProfile';
        }

        if ($paymentsEnabled) {
            $counts[] = 'recordedPayments';
        }

        if ($assetsEnabled) {
            $counts['assetStakeholders as current_asset_assignments_count'] = fn (Builder $stakeholders) => $stakeholders
                ->whereNull('ends_on');
        }

        if ($maintenanceEnabled) {
            $counts['assignedMaintenanceRequests as open_assignments_count'] = fn (Builder $requests) => $requests
                ->whereIn('status', ['open', 'in_progress']);
        }

        $user = User::query()
            ->with($relations)
            ->withCount($counts)
            ->whereKey($target->id)
            ->firstOrFail();
        $this->access->ensureCanManage($actor, $user);
        $tenantProfile = $tenantsEnabled ? $user->tenantProfile : null;

        if ($tenantProfile && $leasesEnabled) {
            $tenantProfile->setAttribute(
                'active_leases_count',
                $this->assignments
                    ->leases($tenantProfile->leases()->getQuery(), $actor)
                    ->where('status', 'active')
                    ->count(),
            );
        }

        return new UserDetailData(
            user: $user,
            stakeholders: $assetsEnabled
                ? $user->assetStakeholders()
                    ->with('asset:id,portfolio_id,title_en,title_ar,code,status')
                    ->latest()
                    ->limit(8)
                    ->get()
                : collect(),
            maintenance: $maintenanceEnabled
                ? $user->assignedMaintenanceRequests()
                    ->with('asset:id,portfolio_id,title_en,title_ar,code')
                    ->latest('requested_at')
                    ->limit(8)
                    ->get()
                : collect(),
            documents: $documentsEnabled
                ? $user->uploadedDocuments()->latest()->limit(8)->get()
                : collect(),
        );
    }
}
