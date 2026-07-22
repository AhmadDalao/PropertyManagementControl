<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceRequest;
use App\Models\User;

class ManageMaintenance
{
    public function __construct(
        private readonly CreateMaintenance $create,
        private readonly UpdateMaintenance $update,
        private readonly CancelMaintenance $cancel,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): MaintenanceRequest
    {
        return $this->create->handle($actor, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, MaintenanceRequest $request, array $data): MaintenanceRequest
    {
        return $this->update->handle($actor, $request, $data);
    }

    public function cancel(User $actor, MaintenanceRequest $request): bool
    {
        return $this->cancel->handle($actor, $request);
    }
}
