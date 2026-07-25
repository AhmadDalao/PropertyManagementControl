<?php

namespace App\Modules\LeaseMoveOuts\Actions;

use App\Models\Lease;
use App\Models\LeaseMoveOut;
use App\Models\User;
use App\Modules\LeaseMoveOuts\Support\LeaseMoveOutGuard;
use App\Modules\Leases\LeaseLifecycle;
use App\Modules\Leases\Support\LeaseAccess;
use Illuminate\Support\Facades\DB;

final readonly class CompleteLeaseMoveOut
{
    public function __construct(
        private LeaseAccess $access,
        private LeaseMoveOutGuard $guard,
        private LeaseLifecycle $lifecycle,
    ) {}

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
            $this->guard->ensureCompletable($lockedLease, $moveOut);
            $totalDue = (float) $lockedLease->installments()->sum('amount_due');
            $totalPaid = (float) $lockedLease->installments()->sum('amount_paid');

            $moveOut->update([
                'status' => 'completed',
                'completed_by_user_id' => $actor->id,
                'balance_at_completion' => max(0, $totalDue - $totalPaid),
                'completed_at' => now(),
                'cancelled_at' => null,
            ]);

            $this->lifecycle->update($lockedLease, [
                'status' => $lockedLease->status === 'active' ? 'terminated' : 'expired',
            ]);

            return $moveOut->fresh();
        }, attempts: 3);
    }
}
