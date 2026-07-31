<?php

namespace App\Modules\Leases\Actions;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Leases\LeaseLifecycle;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Notifications\Actions\SendOperationalActivityNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TerminateLease
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly LeaseLifecycle $lifecycle,
        private readonly SendOperationalActivityNotification $notifications,
    ) {}

    public function handle(User $actor, Lease $lease): Lease
    {
        $this->access->ensureCanManage($actor, $lease);

        $terminated = DB::transaction(function () use ($actor, $lease): Lease {
            $lockedLease = Lease::query()->lockForUpdate()->findOrFail($lease->id);
            $this->access->ensureCanManage($actor, $lockedLease);

            if ($lockedLease->status !== 'draft') {
                throw ValidationException::withMessages([
                    'lease' => trans('app.errors.active_lease_requires_move_out'),
                ]);
            }

            return $this->lifecycle->update($lockedLease, ['status' => 'terminated']);
        }, attempts: 3);

        $this->notifications->lease($actor, $terminated, 'lease_terminated');

        return $terminated;
    }
}
