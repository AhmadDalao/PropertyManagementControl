<?php

namespace App\Modules\Tenants\Actions;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Tenants\Support\TenantAccess;
use App\Modules\Tenants\Support\TenantInputGuard;
use App\Modules\Tenants\Support\TenantPortalAccountManager;
use App\Modules\Tenants\Support\TenantPortfolioResolver;
use App\Modules\Tenants\Support\TenantProfileAttributes;
use Illuminate\Support\Facades\DB;

final class CreateTenant
{
    public function __construct(
        private readonly TenantAccess $access,
        private readonly TenantInputGuard $input,
        private readonly TenantPortfolioResolver $portfolios,
        private readonly TenantPortalAccountManager $accounts,
        private readonly TenantProfileAttributes $profiles,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): TenantProfile
    {
        $this->access->ensureManager($actor);
        $this->input->validate($data, passwordRequired: true);

        return DB::transaction(function () use ($actor, $data): TenantProfile {
            $portfolioId = $this->portfolios->resolve($actor, $data['portfolio_id'] ?? null);
            $user = $this->accounts->create($portfolioId, $data);
            $tenant = TenantProfile::query()->create([
                'portfolio_id' => $portfolioId,
                'user_id' => $user->id,
                ...$this->profiles->from($data),
            ]);

            return $tenant->load(['user', 'portfolio']);
        }, attempts: 3);
    }
}
