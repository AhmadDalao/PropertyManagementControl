<?php

namespace App\Modules\RentCollection\Support;

use App\Models\CollectionFollowUp;
use App\Models\LeaseInstallment;

final class CollectionFollowUpState
{
    public function resolve(
        LeaseInstallment $installment,
        ?CollectionFollowUp $followUp = null,
    ): string {
        if ($installment->remaining_amount <= 0) {
            return 'settled';
        }

        $latest = $followUp ?? $this->latest($installment);

        if (! $latest) {
            return 'untracked';
        }

        if (
            $latest->outcome === 'promise_to_pay'
            && $latest->promised_on?->isBefore(today())
            && ! $this->promiseWasFulfilled($installment, $latest)
        ) {
            return 'broken';
        }

        if ($latest->next_follow_up_on?->lessThanOrEqualTo(today())) {
            return 'due';
        }

        if (
            $latest->outcome === 'promise_to_pay'
            && $latest->promised_on
            && ! $this->promiseWasFulfilled($installment, $latest)
        ) {
            return 'promised';
        }

        return 'scheduled';
    }

    public function priority(string $state): int
    {
        return match ($state) {
            'broken' => 0,
            'due' => 1,
            'untracked' => 2,
            'promised' => 3,
            'scheduled' => 4,
            default => 5,
        };
    }

    private function latest(LeaseInstallment $installment): ?CollectionFollowUp
    {
        if ($installment->relationLoaded('latestCollectionFollowUp')) {
            return $installment->latestCollectionFollowUp;
        }

        return $installment->latestCollectionFollowUp()->first();
    }

    private function promiseWasFulfilled(
        LeaseInstallment $installment,
        CollectionFollowUp $followUp,
    ): bool {
        if (
            $followUp->promised_amount === null
            || $followUp->outstanding_amount_at_contact === null
        ) {
            return false;
        }

        $collectedAfterContact = max(
            0,
            (float) $followUp->outstanding_amount_at_contact
                - $installment->remaining_amount,
        );

        return $collectedAfterContact + 0.005 >= (float) $followUp->promised_amount;
    }
}
