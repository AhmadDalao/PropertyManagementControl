<?php

namespace App\Modules\Maintenance\Support;

use App\Models\Asset;
use App\Models\TenantProfile;
use App\Models\User;

class MaintenanceReferenceGuard
{
    /** @param array<string, mixed> $data */
    public function ensureBelongsToPortfolio(array $data, int $portfolioId): void
    {
        if (! empty($data['asset_id'])) {
            abort_unless(
                Asset::query()
                    ->whereKey($data['asset_id'])
                    ->where('portfolio_id', $portfolioId)
                    ->exists(),
                422,
                trans('app.errors.asset_portfolio_mismatch'),
            );
        }

        if (! empty($data['tenant_profile_id'])) {
            abort_unless(
                TenantProfile::query()
                    ->whereKey($data['tenant_profile_id'])
                    ->where('portfolio_id', $portfolioId)
                    ->exists(),
                422,
                trans('app.errors.tenant_selection_portfolio_mismatch'),
            );
        }

        if (! empty($data['assigned_to_user_id'])) {
            abort_unless(
                User::query()
                    ->whereKey($data['assigned_to_user_id'])
                    ->where('portfolio_id', $portfolioId)
                    ->whereHas(
                        'roles',
                        fn ($roles) => $roles->whereIn('name', ['owner', 'property_manager']),
                    )
                    ->exists(),
                422,
                trans('app.errors.manager_assignment_invalid'),
            );
        }
    }
}
