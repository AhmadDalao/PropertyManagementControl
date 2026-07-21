<?php

namespace App\Modules\Search\Support;

use App\Models\User;

class SearchAccess
{
    public function ensureAllowed(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner', 'property_manager', 'tenant']),
            403,
            trans('app.errors.section_access_denied'),
        );
    }
}
