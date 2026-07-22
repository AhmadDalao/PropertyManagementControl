<?php

namespace App\Modules\Leases\Support;

use App\Models\Asset;
use App\Models\TenantProfile;
use Illuminate\Validation\ValidationException;

final class LeaseParticipants
{
    public function asset(int $assetId, int $portfolioId): Asset
    {
        $asset = Asset::query()->lockForUpdate()->findOrFail($assetId);

        abort_if(
            $asset->portfolio_id !== $portfolioId,
            422,
            trans('app.errors.lease_asset_portfolio_mismatch'),
        );

        if (! $asset->rentable || $asset->status !== 'active') {
            throw ValidationException::withMessages([
                'asset_id' => trans('app.errors.asset_not_rentable'),
            ]);
        }

        return $asset;
    }

    public function tenant(
        int $tenantId,
        int $portfolioId,
        bool $allowInactivePortal = false,
    ): TenantProfile {
        $tenant = TenantProfile::query()->lockForUpdate()->findOrFail($tenantId);

        abort_if(
            $tenant->portfolio_id !== $portfolioId,
            422,
            trans('app.errors.tenant_portfolio_mismatch'),
        );

        $user = $tenant->user()->lockForUpdate()->first();

        if (
            $tenant->status !== 'active'
            || ! $user
            || (! $allowInactivePortal && $user->status !== 'active')
        ) {
            throw ValidationException::withMessages([
                'tenant_profile_id' => trans('app.errors.lease_tenant_inactive'),
            ]);
        }

        return $tenant;
    }
}
