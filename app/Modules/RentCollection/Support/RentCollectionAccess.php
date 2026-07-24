<?php

namespace App\Modules\RentCollection\Support;

use App\Models\User;

final class RentCollectionAccess
{
    public function ensureManager(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']),
            403,
            trans('app.errors.section_access_denied'),
        );
    }
}
