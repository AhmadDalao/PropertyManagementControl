<?php

namespace App\Modules\RentCollection\Presenters;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Assets\Support\AssetHierarchy;
use App\Modules\Leases\Presenters\LeaseInstallmentLabelPresenter;
use App\Modules\RentCollection\Queries\CollectionAssigneeQuery;
use App\Modules\RentCollection\Support\CollectionFollowUpAccess;
use App\Modules\RentCollection\Support\CollectionFollowUpOptions;

final readonly class CollectionFollowUpPagePresenter
{
    public function __construct(
        private CollectionFollowUpAccess $access,
        private CollectionAssigneeQuery $assignees,
        private CollectionFollowUpPresenter $followUps,
        private RentCollectionRowPresenter $rows,
        private LeaseInstallmentLabelPresenter $labels,
        private AssetHierarchy $assets,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, LeaseInstallment $target): array
    {
        $this->access->ensureCanManage($actor, $target);
        $installment = LeaseInstallment::query()
            ->with([
                'lease.portfolio',
                'lease.managedBy',
                'lease.tenantProfile.user',
                'lease.leaseable',
                'latestCollectionFollowUp.assignedTo',
                'latestCollectionFollowUp.recordedBy',
            ])
            ->withCount('collectionFollowUps')
            ->findOrFail($target->id);
        $this->access->ensureCanManage($actor, $installment);
        $lease = $installment->lease;
        abort_unless($lease instanceof Lease, 404);
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;
        $property = $asset ? $this->assets->root($asset) : null;
        $history = $installment->collectionFollowUps()
            ->with(['assignedTo:id,name', 'recordedBy:id,name'])
            ->latest('contacted_at')
            ->latest('id')
            ->limit(25)
            ->get();
        $assignees = $this->assignees->options($actor, $lease);
        $latest = $installment->latestCollectionFollowUp;
        $defaultAssignee = collect($assignees)
            ->firstWhere('id', $latest?->assigned_to_user_id)
            ?? collect($assignees)->firstWhere('id', $lease->managed_by_user_id)
            ?? collect($assignees)->firstWhere('id', $actor->id)
            ?? $assignees[0]
            ?? null;
        $status = $this->rows->status($installment);
        $tenantUser = $lease->tenantProfile?->user;

        return [
            'installment' => [
                'id' => $installment->id,
                'label' => $this->labels->present($installment),
                'line_type' => $installment->line_type,
                'due_date' => $installment->due_date?->toDateString(),
                'amount_due' => (float) $installment->amount_due,
                'amount_paid' => (float) $installment->amount_paid,
                'outstanding_amount' => $installment->remaining_amount,
                'currency' => $lease->currency ?: 'SAR',
                'status' => $status,
                'days_overdue' => $installment->due_date?->isBefore(today())
                    ? (int) $installment->due_date->diffInDays(today())
                    : 0,
                'is_showcase' => $lease->getIsShowcaseAttribute(),
            ],
            'lease' => [
                'id' => $lease->id,
                'code' => $lease->code,
                'status' => $lease->status,
            ],
            'tenant' => [
                'name' => $tenantUser instanceof User
                    ? $tenantUser->name
                    : (string) trans('app.rent_collection.no_tenant'),
                'email' => $tenantUser instanceof User ? $tenantUser->email : null,
                'phone' => $tenantUser instanceof User ? $tenantUser->phone : null,
            ],
            'asset' => $this->asset($asset),
            'property' => $this->asset($property),
            'latest_follow_up' => $this->followUps->latest($installment),
            'history' => $this->followUps->history($history),
            'history_truncated' => $installment->collection_follow_ups_count > $history->count(),
            'assignee_options' => $assignees,
            'contact_method_options' => $this->options(
                CollectionFollowUpOptions::CONTACT_METHODS,
                'contact_method',
            ),
            'outcome_options' => $this->options(
                CollectionFollowUpOptions::OUTCOMES,
                'outcome',
            ),
            'initial_values' => [
                'contact_method' => 'phone',
                'outcome' => 'contacted',
                'contacted_at' => now()->format('Y-m-d\TH:i'),
                'assigned_to_user_id' => $defaultAssignee['id'] ?? '',
                'next_follow_up_on' => today()->addDay()->toDateString(),
                'promised_amount' => $installment->remaining_amount,
                'promised_on' => today()->addDay()->toDateString(),
                'note' => '',
            ],
            'can_record' => $installment->remaining_amount > 0,
            'links' => [
                'back' => route('rent-collection.index'),
                'store' => route('rent-collection.follow-ups.store', $installment),
                'lease' => route('leases.show', $lease),
                'payment' => route('payments.create', ['lease_id' => $lease->id]),
                'statement' => route('leases.statement', $lease),
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    private function asset(?Asset $asset): ?array
    {
        return $asset ? [
            'id' => $asset->id,
            'title_en' => $asset->title_en,
            'title_ar' => $asset->title_ar,
            'code' => $asset->code,
        ] : null;
    }

    /**
     * @param  list<string>  $values
     * @return list<array{value:string,label:string}>
     */
    private function options(array $values, string $group): array
    {
        $options = [];

        foreach ($values as $value) {
            $options[] = [
                'value' => $value,
                'label' => (string) trans("app.rent_collection.{$group}_{$value}"),
            ];
        }

        return $options;
    }
}
