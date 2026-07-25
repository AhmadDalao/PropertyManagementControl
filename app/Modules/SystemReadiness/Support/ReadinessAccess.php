<?php

namespace App\Modules\SystemReadiness\Support;

use App\Models\User;

final class ReadinessAccess
{
    public function ensureSuperadmin(User $actor): void
    {
        abort_unless(
            $actor->hasRole('superadmin'),
            403,
            trans('app.errors.section_access_denied'),
        );
    }
}
