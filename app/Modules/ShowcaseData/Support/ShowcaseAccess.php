<?php

namespace App\Modules\ShowcaseData\Support;

use App\Models\User;

final class ShowcaseAccess
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
