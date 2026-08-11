<?php

namespace App\Modules\TenantPortal\Support;

use App\Models\TenantProfile;
use App\Models\User;

final class TenantPortalAccess
{
    public function profile(User $actor): ?TenantProfile
    {
        abort_unless(
            $actor->hasRole('tenant'),
            403,
            trans('app.errors.section_access_denied'),
        );

        return $actor->tenantProfile()->first();
    }
}
