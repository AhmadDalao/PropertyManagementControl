<?php

namespace App\Modules\Tenants\Actions;

use App\Models\TenantProfile;
use App\Models\User;

final class ManageTenants
{
    public function __construct(
        private readonly CreateTenant $create,
        private readonly UpdateTenant $update,
        private readonly ArchiveTenant $archive,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): TenantProfile
    {
        return $this->create->handle($actor, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, TenantProfile $tenant, array $data): TenantProfile
    {
        return $this->update->handle($actor, $tenant, $data);
    }

    public function archive(User $actor, TenantProfile $tenant): bool
    {
        return $this->archive->handle($actor, $tenant);
    }
}
