<?php

namespace App\Modules\ActionCenter\Support;

use App\Models\User;

final class ActionCenterAccess
{
    public function ensureCanView(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']),
            403,
            trans('app.errors.section_access_denied'),
        );
    }
}
