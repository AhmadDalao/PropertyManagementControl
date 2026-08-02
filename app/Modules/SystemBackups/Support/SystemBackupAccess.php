<?php

namespace App\Modules\SystemBackups\Support;

use App\Models\User;

final class SystemBackupAccess
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
