<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\CollectionFollowUp;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\User;

final class ShowcaseCollectionFollowUpBuilder
{
    /** @param array<int, Lease> $leases */
    public function build(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        User $manager,
        array $leases,
        int $buildingIndex,
    ): int {
        $installments = LeaseInstallment::query()
            ->whereIn('lease_id', collect($leases)->pluck('id'))
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->whereDate('due_date', '<', today())
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit(3)
            ->get();

        foreach ($installments as $index => $installment) {
            $outcome = match ($index) {
                0 => 'promise_to_pay',
                1 => 'no_answer',
                default => 'contacted',
            };
            $note = "Tagged showcase collection follow-up {$dataset->key} building ".($buildingIndex + 1).' slot '.($index + 1).'.';

            $followUp = CollectionFollowUp::query()->firstOrCreate(
                [
                    'lease_installment_id' => $installment->id,
                    'note' => $note,
                ],
                [
                    'portfolio_id' => $portfolio->id,
                    'lease_id' => $installment->lease_id,
                    'recorded_by_user_id' => $manager->id,
                    'assigned_to_user_id' => $manager->id,
                    'contact_method' => $index === 1 ? 'phone' : 'whatsapp',
                    'outcome' => $outcome,
                    'contacted_at' => now()->subDays(4 - $index),
                    'outstanding_amount_at_contact' => $installment->remaining_amount,
                    'promised_amount' => $outcome === 'promise_to_pay'
                        ? $installment->remaining_amount
                        : null,
                    'promised_on' => $outcome === 'promise_to_pay'
                        ? today()->subDay()
                        : null,
                    'next_follow_up_on' => match ($index) {
                        0 => today()->subDay(),
                        1 => today(),
                        default => today()->addDays(3),
                    },
                ],
            );

            if ($followUp->outstanding_amount_at_contact === null) {
                $followUp->update([
                    'outstanding_amount_at_contact' => $installment->remaining_amount,
                ]);
            }
        }

        return $installments->count();
    }
}
