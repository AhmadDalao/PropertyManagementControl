<?php

namespace App\Modules\RentCollection\Support;

use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Leases\Support\LeaseAccess;

final readonly class CollectionFollowUpAccess
{
    public function __construct(private LeaseAccess $leases) {}

    public function ensureCanManage(User $actor, LeaseInstallment $installment): void
    {
        $installment->loadMissing('lease');

        abort_unless(
            $installment->lease && $this->leases->canManage($actor, $installment->lease),
            403,
            trans('app.errors.section_access_denied'),
        );
    }
}
