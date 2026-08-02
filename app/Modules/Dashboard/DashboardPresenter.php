<?php

namespace App\Modules\Dashboard;

use App\Models\User;
use App\Modules\Dashboard\Presenters\OperationsDashboardPresenter;
use App\Modules\Dashboard\Presenters\TenantDashboardPresenter;
use App\Modules\Dashboard\Support\DashboardPayloadCache;

class DashboardPresenter
{
    public function __construct(
        private readonly OperationsDashboardPresenter $operations,
        private readonly TenantDashboardPresenter $tenant,
        private readonly DashboardPayloadCache $cache,
    ) {}

    /** @return array<string, mixed> */
    public function forUser(User $user, ?int $propertyId = null): array
    {
        return $this->cache->remember(
            $user,
            $propertyId,
            fn (): array => $user->hasRole('tenant')
                ? $this->tenant->present($user)
                : $this->operations->present($user, $propertyId),
        );
    }
}
