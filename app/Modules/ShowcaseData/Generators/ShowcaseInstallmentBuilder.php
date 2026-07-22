<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\Lease;
use App\Models\LeaseInstallment;
use Illuminate\Support\Carbon;

class ShowcaseInstallmentBuilder
{
    public function build(Lease $lease): void
    {
        $leaseStart = Carbon::parse((string) $lease->started_at);

        for ($sequence = 1; $sequence <= 12; $sequence++) {
            $periodStart = $leaseStart->copy()->addMonths($sequence - 1)->startOfMonth();
            LeaseInstallment::query()->updateOrCreate(
                ['lease_id' => $lease->id, 'sequence' => $sequence],
                [
                    'line_type' => 'rent',
                    'label' => "Rent {$sequence}",
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodStart->copy()->endOfMonth()->toDateString(),
                    'due_date' => $periodStart->toDateString(),
                    'amount_due' => $lease->rent_amount,
                    'amount_paid' => 0,
                    'status' => $periodStart->isPast() ? 'overdue' : 'pending',
                    'paid_at' => null,
                    'notes' => 'Showcase installment.',
                ],
            );
        }
    }
}
