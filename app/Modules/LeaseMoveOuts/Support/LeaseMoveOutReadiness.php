<?php

namespace App\Modules\LeaseMoveOuts\Support;

use App\Models\Lease;
use App\Models\LeaseMoveOut;

final class LeaseMoveOutReadiness
{
    /**
     * @return array{
     *     notice:bool,
     *     inspection:bool,
     *     keys:bool,
     *     deposit:bool,
     *     date_reached:bool,
     *     ready:bool,
     *     balance:float
     * }
     */
    public function for(Lease $lease, LeaseMoveOut $moveOut): array
    {
        $documents = $lease->relationLoaded('documents')
            ? $lease->documents
            : $lease->documents()->get(['type']);
        $notice = $documents->contains('type', 'termination_notice');
        $inspection = $documents->contains('type', 'move_out_inspection');
        $keys = (bool) $moveOut->keys_returned;
        $deposit = $moveOut->deposit_disposition !== 'pending';
        $dateReached = $moveOut->move_out_date?->lessThanOrEqualTo(today()) ?? false;

        return [
            'notice' => $notice,
            'inspection' => $inspection,
            'keys' => $keys,
            'deposit' => $deposit,
            'date_reached' => $dateReached,
            'ready' => $moveOut->status === 'planned'
                && $notice
                && $inspection
                && $keys
                && $deposit
                && $dateReached,
            'balance' => $this->balance($lease),
        ];
    }

    private function balance(Lease $lease): float
    {
        if ($lease->relationLoaded('installments')) {
            return max(
                0,
                (float) $lease->installments->sum('amount_due')
                    - (float) $lease->installments->sum('amount_paid'),
            );
        }

        return max(
            0,
            (float) $lease->installments()->sum('amount_due')
                - (float) $lease->installments()->sum('amount_paid'),
        );
    }
}
