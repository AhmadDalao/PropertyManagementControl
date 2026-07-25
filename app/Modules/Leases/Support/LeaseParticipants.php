<?php

namespace App\Modules\Leases\Support;

use App\Models\Asset;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use Illuminate\Validation\ValidationException;

final class LeaseParticipants
{
    public function __construct(private readonly AssignedPropertyScope $assignments) {}

    public function asset(User $actor, int $assetId, int $portfolioId): Asset
    {
        $asset = Asset::query()->lockForUpdate()->findOrFail($assetId);

        abort_if(
            $asset->portfolio_id !== $portfolioId,
            422,
            trans('app.errors.lease_asset_portfolio_mismatch'),
        );
        abort_unless(
            $this->assignments->allowsAsset($actor, $asset),
            403,
            trans('app.errors.property_assignment_access_denied'),
        );

        if (! $asset->rentable || $asset->status !== 'active') {
            throw ValidationException::withMessages([
                'asset_id' => trans('app.errors.asset_not_rentable'),
            ]);
        }

        return $asset;
    }

    public function tenant(
        User $actor,
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
        abort_unless(
            $this->assignments->allowsTenant($actor, $tenant),
            403,
            trans('app.errors.property_assignment_access_denied'),
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
