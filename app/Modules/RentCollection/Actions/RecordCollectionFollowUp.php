<?php

namespace App\Modules\RentCollection\Actions;

use App\Models\CollectionFollowUp;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\RentCollection\Queries\CollectionAssigneeQuery;
use App\Modules\RentCollection\Support\CollectionFollowUpAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecordCollectionFollowUp
{
    public function __construct(
        private CollectionFollowUpAccess $access,
        private CollectionAssigneeQuery $assignees,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(
        User $actor,
        LeaseInstallment $installment,
        array $data,
    ): CollectionFollowUp {
        return DB::transaction(function () use ($actor, $installment, $data): CollectionFollowUp {
            $locked = LeaseInstallment::query()
                ->with('lease')
                ->lockForUpdate()
                ->findOrFail($installment->id);
            $this->access->ensureCanManage($actor, $locked);
            $lease = $locked->lease;

            if (
                ! $lease
                || ! in_array($lease->status, PaymentOptions::PAYABLE_LEASE_STATUSES, true)
                || $locked->remaining_amount <= 0
            ) {
                throw ValidationException::withMessages([
                    'installment' => trans('app.errors.collection_follow_up_closed'),
                ]);
            }

            $assigneeId = (int) $data['assigned_to_user_id'];
            if (! $this->assignees->allows($actor, $lease, $assigneeId)) {
                throw ValidationException::withMessages([
                    'assigned_to_user_id' => trans('app.errors.collection_assignee_invalid'),
                ]);
            }

            $promiseAmount = $data['outcome'] === 'promise_to_pay'
                ? (float) $data['promised_amount']
                : null;
            if ($promiseAmount !== null && $promiseAmount > $locked->remaining_amount) {
                throw ValidationException::withMessages([
                    'promised_amount' => trans('app.errors.collection_promise_exceeds_balance'),
                ]);
            }

            return $locked->collectionFollowUps()->create([
                'portfolio_id' => $lease->portfolio_id,
                'lease_id' => $lease->id,
                'recorded_by_user_id' => $actor->id,
                'assigned_to_user_id' => $assigneeId,
                'contact_method' => $data['contact_method'],
                'outcome' => $data['outcome'],
                'contacted_at' => $data['contacted_at'],
                'outstanding_amount_at_contact' => $locked->remaining_amount,
                'promised_amount' => $promiseAmount,
                'promised_on' => $data['outcome'] === 'promise_to_pay'
                    ? $data['promised_on']
                    : null,
                'next_follow_up_on' => $data['next_follow_up_on'],
                'note' => trim((string) $data['note']),
            ]);
        }, attempts: 3);
    }
}
