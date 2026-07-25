<?php

namespace App\Modules\RentCollection\Presenters;

use App\Models\CollectionFollowUp;
use App\Models\LeaseInstallment;
use App\Modules\RentCollection\Support\CollectionFollowUpState;
use Illuminate\Support\Collection;

final readonly class CollectionFollowUpPresenter
{
    public function __construct(private CollectionFollowUpState $states) {}

    /** @return array<string, mixed> */
    public function latest(LeaseInstallment $installment): array
    {
        $followUp = $installment->latestCollectionFollowUp;
        $state = $this->states->resolve($installment, $followUp);

        if (! $followUp) {
            return [
                'state' => $state,
                'history_count' => (int) ($installment->collection_follow_ups_count ?? 0),
            ];
        }

        return [
            ...$this->record($followUp),
            'state' => $state,
            'history_count' => (int) ($installment->collection_follow_ups_count ?? 0),
        ];
    }

    /**
     * @param  Collection<int, CollectionFollowUp>  $followUps
     * @return list<array<string, mixed>>
     */
    public function history(Collection $followUps): array
    {
        $history = [];

        foreach ($followUps as $followUp) {
            $history[] = $this->record($followUp);
        }

        return $history;
    }

    /** @return array<string, mixed> */
    private function record(CollectionFollowUp $followUp): array
    {
        return [
            'id' => $followUp->id,
            'contact_method' => $followUp->contact_method,
            'outcome' => $followUp->outcome,
            'contacted_at' => $followUp->contacted_at?->toIso8601String(),
            'promised_amount' => $followUp->promised_amount !== null
                ? (float) $followUp->promised_amount
                : null,
            'promised_on' => $followUp->promised_on?->toDateString(),
            'next_follow_up_on' => $followUp->next_follow_up_on?->toDateString(),
            'note' => $followUp->note,
            'assigned_to' => $followUp->assignedTo ? [
                'id' => $followUp->assignedTo->id,
                'name' => $followUp->assignedTo->name,
            ] : null,
            'recorded_by' => $followUp->recordedBy ? [
                'id' => $followUp->recordedBy->id,
                'name' => $followUp->recordedBy->name,
            ] : null,
        ];
    }
}
