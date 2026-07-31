<?php

namespace App\Modules\Leases\Actions;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Leases\LeaseLifecycle;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Leases\Support\LeaseAttributes;
use App\Modules\Leases\Support\LeaseInputGuard;
use App\Modules\Notifications\Actions\SendOperationalActivityNotification;
use Illuminate\Support\Facades\DB;

final class UpdateLease
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly LeaseInputGuard $input,
        private readonly LeaseAttributes $attributes,
        private readonly LeaseLifecycle $lifecycle,
        private readonly SendOperationalActivityNotification $notifications,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, Lease $lease, array $data): Lease
    {
        $this->access->ensureCanManage($actor, $lease);
        $this->input->validateUpdate($data);

        $previousStatus = (string) $lease->status;
        $updated = DB::transaction(function () use ($actor, $lease, $data, &$previousStatus): Lease {
            $lockedLease = Lease::query()->lockForUpdate()->findOrFail($lease->id);
            $this->access->ensureCanManage($actor, $lockedLease);
            $previousStatus = (string) $lockedLease->status;

            return $this->lifecycle->update($lockedLease, $this->attributes->forUpdate($data));
        }, attempts: 3);

        $event = match (true) {
            $updated->status === 'active' && $previousStatus !== 'active' => 'lease_activated',
            $updated->status === 'terminated' && $previousStatus !== 'terminated' => 'lease_terminated',
            default => 'lease_updated',
        };
        $this->notifications->lease($actor, $updated, $event);

        return $updated;
    }
}
