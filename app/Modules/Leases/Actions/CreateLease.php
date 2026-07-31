<?php

namespace App\Modules\Leases\Actions;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Leases\LeaseLifecycle;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Leases\Support\LeaseAttributes;
use App\Modules\Leases\Support\LeaseInputGuard;
use App\Modules\Leases\Support\LeaseParticipants;
use App\Modules\Leases\Support\LeasePortfolioResolver;
use App\Modules\Leases\Support\LeaseRenewalGuard;
use App\Modules\Notifications\Actions\SendOperationalActivityNotification;
use Illuminate\Support\Facades\DB;

final class CreateLease
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly LeaseInputGuard $input,
        private readonly LeasePortfolioResolver $portfolios,
        private readonly LeaseParticipants $participants,
        private readonly LeaseRenewalGuard $renewals,
        private readonly LeaseAttributes $attributes,
        private readonly LeaseLifecycle $lifecycle,
        private readonly SendOperationalActivityNotification $notifications,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): Lease
    {
        $this->access->ensureManager($actor);
        $this->input->validateCreate($data);

        $lease = DB::transaction(function () use ($actor, $data): Lease {
            $portfolioId = $this->portfolios->resolve($actor, $data['portfolio_id'] ?? null);
            $asset = $this->participants->asset($actor, (int) $data['asset_id'], $portfolioId);
            $tenant = $this->participants->tenant(
                $actor,
                (int) $data['tenant_profile_id'],
                $portfolioId,
                allowInactivePortal: ! empty($data['renewed_from_lease_id']),
            );
            $this->renewals->validateCreation($actor, $data, $portfolioId, $tenant, $asset);

            return $this->lifecycle->create(
                $asset,
                $this->attributes->forCreate($actor, $portfolioId, $tenant, $asset, $data),
            );
        }, attempts: 3);

        $event = match (true) {
            $lease->renewed_from_lease_id !== null => 'lease_renewal_created',
            $lease->status === 'active' => 'lease_activated',
            default => 'lease_created',
        };
        $this->notifications->lease($actor, $lease, $event);

        return $lease;
    }
}
