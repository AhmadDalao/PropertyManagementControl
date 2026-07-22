<?php

namespace App\Modules\Leases\Actions;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Leases\LeaseLifecycle;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Leases\Support\LeaseAttributes;
use App\Modules\Leases\Support\LeaseInputGuard;
use Illuminate\Support\Facades\DB;

final class UpdateLease
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly LeaseInputGuard $input,
        private readonly LeaseAttributes $attributes,
        private readonly LeaseLifecycle $lifecycle,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, Lease $lease, array $data): Lease
    {
        $this->access->ensureCanManage($actor, $lease);
        $this->input->validateUpdate($data);

        return DB::transaction(function () use ($actor, $lease, $data): Lease {
            $lockedLease = Lease::query()->lockForUpdate()->findOrFail($lease->id);
            $this->access->ensureCanManage($actor, $lockedLease);

            return $this->lifecycle->update($lockedLease, $this->attributes->forUpdate($data));
        }, attempts: 3);
    }
}
