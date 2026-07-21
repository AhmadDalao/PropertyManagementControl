<?php

namespace App\Modules\Dashboard;

use App\Models\User;
use App\Modules\Dashboard\Presenters\OperationsDashboardPresenter;
use App\Modules\Dashboard\Presenters\TenantDashboardPresenter;

class DashboardPresenter
{
    public function __construct(
        private readonly OperationsDashboardPresenter $operations,
        private readonly TenantDashboardPresenter $tenant,
    ) {}

    /** @return array<string, mixed> */
    public function forUser(User $user): array
    {
        return $user->hasRole('tenant')
            ? $this->tenant->present($user)
            : $this->operations->present($user);
    }
}
