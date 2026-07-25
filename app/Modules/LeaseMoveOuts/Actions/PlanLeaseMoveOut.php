<?php

namespace App\Modules\LeaseMoveOuts\Actions;

use App\Models\Lease;
use App\Models\LeaseMoveOut;
use App\Models\User;
use App\Modules\LeaseMoveOuts\Support\LeaseMoveOutGuard;
use App\Modules\Leases\Support\LeaseAccess;
use Illuminate\Support\Facades\DB;

final readonly class PlanLeaseMoveOut
{
    public function __construct(
        private LeaseAccess $access,
        private LeaseMoveOutGuard $guard,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, Lease $lease, array $data): LeaseMoveOut
    {
        $this->access->ensureCanManage($actor, $lease);

        return DB::transaction(function () use ($actor, $lease, $data): LeaseMoveOut {
            $lockedLease = Lease::query()->lockForUpdate()->findOrFail($lease->id);
            $this->access->ensureCanManage($actor, $lockedLease);
            $this->guard->ensurePlannable($lockedLease);
            $this->guard->validatePlan($lockedLease, $data);
            $moveOut = LeaseMoveOut::query()
                ->where('lease_id', $lockedLease->id)
                ->lockForUpdate()
                ->first();

            if ($moveOut?->status === 'completed') {
                abort(409, trans('app.errors.move_out_completed_locked'));
            }

            $attributes = [
                'portfolio_id' => $lockedLease->portfolio_id,
                'initiated_by_user_id' => $moveOut?->initiated_by_user_id ?: $actor->id,
                'status' => 'planned',
                'move_out_date' => $data['move_out_date'],
                'reason' => $data['reason'],
                'deposit_disposition' => $data['deposit_disposition'],
                'deposit_deduction_amount' => $data['deposit_deduction_amount'] ?? 0,
                'keys_returned' => (bool) ($data['keys_returned'] ?? false),
                'notes' => $data['notes'] ?? null,
                'cancelled_at' => null,
            ];

            if ($moveOut) {
                $moveOut->update($attributes);

                return $moveOut->fresh();
            }

            return $lockedLease->moveOut()->create($attributes);
        }, attempts: 3);
    }
}
