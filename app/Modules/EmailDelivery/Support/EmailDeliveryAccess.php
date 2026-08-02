<?php

namespace App\Modules\EmailDelivery\Support;

use App\Models\User;

final class EmailDeliveryAccess
{
    public function ensureSuperadmin(User $actor): void
    {
        abort_unless($actor->hasRole('superadmin'), 403);
    }
}
