<?php

namespace App\Modules\LeaseMoveOuts\Actions;

use App\Models\Lease;
use App\Models\LeaseMoveOut;
use App\Models\User;
use App\Modules\Leases\Support\LeaseAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CancelLeaseMoveOut
{
    public function __construct(private LeaseAccess $access) {}

    public function handle(User $actor, Lease $lease): LeaseMoveOut
    {
        $this->access->ensureCanManage($actor, $lease);

        return DB::transaction(function () use ($actor, $lease): LeaseMoveOut {
            $lockedLease = Lease::query()->lockForUpdate()->findOrFail($lease->id);
            $this->access->ensureCanManage($actor, $lockedLease);
            $moveOut = LeaseMoveOut::query()
                ->where('lease_id', $lockedLease->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($moveOut->status !== 'planned') {
                throw ValidationException::withMessages([
                    'move_out' => trans($moveOut->status === 'completed'
                        ? 'app.errors.move_out_completed_locked'
                        : 'app.errors.move_out_not_planned'),
                ]);
            }

            $moveOut->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return $moveOut->fresh();
        }, attempts: 3);
    }
}
