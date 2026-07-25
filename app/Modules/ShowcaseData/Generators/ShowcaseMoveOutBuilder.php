<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\Lease;
use App\Models\LeaseMoveOut;
use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\User;

final class ShowcaseMoveOutBuilder
{
    /** @param list<Lease> $leases */
    public function build(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        User $manager,
        array $leases,
        int $buildingIndex,
    ): ?LeaseMoveOut {
        $lease = collect($leases)
            ->filter(fn (Lease $lease): bool => in_array($lease->status, ['active', 'expired'], true))
            ->sortBy(fn (Lease $lease): string => ($lease->status === 'expired' ? '0' : '1').$lease->code)
            ->first();

        if (! $lease instanceof Lease) {
            return null;
        }

        $offset = match ($buildingIndex % 3) {
            0 => -7,
            1 => 0,
            default => 14,
        };

        return LeaseMoveOut::query()->firstOrCreate(
            ['lease_id' => $lease->id],
            [
                'portfolio_id' => $portfolio->id,
                'initiated_by_user_id' => $manager->id,
                'status' => 'planned',
                'move_out_date' => today()->addDays($offset),
                'reason' => $lease->status === 'expired' ? 'natural_expiry' : 'tenant_notice',
                'deposit_disposition' => 'pending',
                'deposit_deduction_amount' => 0,
                'keys_returned' => false,
                'notes' => "Tagged showcase handover for {$dataset->key}.",
            ],
        );
    }
}
