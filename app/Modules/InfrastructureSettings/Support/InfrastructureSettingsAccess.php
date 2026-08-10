<?php

namespace App\Modules\InfrastructureSettings\Support;

use App\Models\User;

final class InfrastructureSettingsAccess
{
    public function ensureSuperadmin(User $actor): void
    {
        abort_unless($actor->hasRole('superadmin'), 403);
    }
}
