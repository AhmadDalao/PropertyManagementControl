<?php

namespace App\Modules\Tenants\Presenters;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Tenants\Support\TenantAccess;

final class TenantFormPresenter
{
    public function __construct(
        private readonly TenantAccess $access,
        private readonly TenantCreateFormPresenter $create,
        private readonly TenantEditFormPresenter $edit,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?TenantProfile $tenant = null, array $defaults = []): array
    {
        if ($tenant) {
            $this->access->ensureCanManage($actor, $tenant);
            $tenant->loadMissing('user');

            return $this->edit->present($actor, $tenant);
        }

        $this->access->ensureManager($actor);

        return $this->create->present($actor, $defaults);
    }
}
