<?php

namespace App\Modules\Cms\Support;

use App\Models\User;

class CmsAccess
{
    public function ensureAdmin(User $actor): void
    {
        abort_unless(
            $actor->hasRole('superadmin'),
            403,
            trans('app.errors.section_access_denied'),
        );
    }
}
