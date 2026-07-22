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
use Illuminate\Support\Facades\DB;

final class CreateLease
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly LeaseInputGuard $input,
        private readonly LeasePortfolioResolver $portfolios,
        private readonly LeaseParticipants $participants,
        private readonly LeaseAttributes $attributes,
        private readonly LeaseLifecycle $lifecycle,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): Lease
    {
        $this->access->ensureManager($actor);
        $this->input->validateCreate($data);

        return DB::transaction(function () use ($actor, $data): Lease {
            $portfolioId = $this->portfolios->resolve($actor, $data['portfolio_id'] ?? null);
            $asset = $this->participants->asset((int) $data['asset_id'], $portfolioId);
            $tenant = $this->participants->tenant((int) $data['tenant_profile_id'], $portfolioId);

            return $this->lifecycle->create(
                $asset,
                $this->attributes->forCreate($actor, $portfolioId, $tenant, $asset, $data),
            );
        }, attempts: 3);
    }
}
