<?php

namespace App\Modules\Maintenance\Support;

use App\Models\Asset;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;

class MaintenanceReferenceGuard
{
    public function __construct(private readonly AssignedPropertyScope $assignments) {}

    /** @param array<string, mixed> $data */
    public function ensureBelongsToPortfolio(
        User $actor,
        array $data,
        int $portfolioId,
        ?int $contextAssetId = null,
    ): void {
        $assetId = ! empty($data['asset_id']) ? (int) $data['asset_id'] : $contextAssetId;
        $asset = $assetId
            ? Asset::query()->whereKey($assetId)->where('portfolio_id', $portfolioId)->first()
            : null;

        if ($assetId !== null) {
            abort_unless(
                $asset && $this->assignments->allowsAsset($actor, $asset),
                422,
                trans('app.errors.asset_portfolio_mismatch'),
            );
        }

        if (! empty($data['tenant_profile_id'])) {
            $tenant = TenantProfile::query()
                ->whereKey($data['tenant_profile_id'])
                ->where('portfolio_id', $portfolioId)
                ->first();
            abort_unless(
                $tenant && $this->assignments->allowsTenant($actor, $tenant),
                422,
                trans('app.errors.tenant_selection_portfolio_mismatch'),
            );
        }

        if (! empty($data['assigned_to_user_id'])) {
            $assignee = User::query()
                ->whereKey($data['assigned_to_user_id'])
                ->where('portfolio_id', $portfolioId)
                ->whereHas(
                    'roles',
                    fn ($roles) => $roles->whereIn('name', ['owner', 'property_manager']),
                )
                ->first();
            abort_unless(
                $assignee
                    && ($asset === null || $this->assignments->allowsAsset($assignee, $asset)),
                422,
                trans('app.errors.manager_assignment_invalid'),
            );
        }
    }
}
