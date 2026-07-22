<?php

namespace App\Modules\Tenants\Data;

use App\Models\TenantProfile;
use App\Models\User;

final readonly class TenantFormData
{
    /** @param array<string, mixed> $defaults */
    public function __construct(
        public User $actor,
        public ?TenantProfile $tenant = null,
        public array $defaults = [],
    ) {}
}
