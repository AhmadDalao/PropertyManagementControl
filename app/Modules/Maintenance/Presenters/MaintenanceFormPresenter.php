<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceRequest;
use App\Models\User;

class MaintenanceFormPresenter
{
    public function __construct(
        private readonly MaintenanceCreateFormPresenter $create,
        private readonly MaintenanceTriageFormPresenter $triage,
    ) {}

    /**
     * @param  array{portfolio_id?:mixed,asset_id?:mixed,tenant_profile_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?MaintenanceRequest $request = null, array $defaults = []): array
    {
        return $request
            ? $this->triage->present($actor, $request)
            : $this->create->present($actor, $defaults);
    }
}
