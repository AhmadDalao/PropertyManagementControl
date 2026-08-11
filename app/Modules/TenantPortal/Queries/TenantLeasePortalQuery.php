<?php

namespace App\Modules\TenantPortal\Queries;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Documents\Presenters\DocumentTableRowPresenter;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\TenantPortal\Support\TenantPortalAccess;
use Illuminate\Database\Eloquent\Builder;

final readonly class TenantLeasePortalQuery
{
    public function __construct(
        private TenantPortalAccess $access,
        private DocumentAccess $documents,
        private DocumentAttachments $attachments,
        private DocumentTableRowPresenter $documentRows,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $actor, ?int $leaseId): array
    {
        $profile = $this->access->profile($actor);
        $base = Lease::query()
            ->when(
                $profile,
                fn (Builder $query) => $query->where('tenant_profile_id', $profile->id),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
            );

        $leases = (clone $base)
            ->with('leaseable')
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END")
            ->latest('ends_at')
            ->get();

        $lease = $leaseId
            ? (clone $base)->with(['leaseable', 'installments'])->findOrFail($leaseId)
            : $leases->first()?->loadMissing(['leaseable', 'installments']);

        $schedule = $lease
            ? $lease->installments()->orderBy('sequence')->paginate(12, ['*'], 'schedule_page')->withQueryString()
            : LeaseInstallment::query()->whereRaw('1 = 0')->paginate(12, ['*'], 'schedule_page');

        $documentRows = $lease
            ? $this->documents->tenantPortalScope(Document::query(), $actor)
                ->whereIn('documentable_type', $this->attachments->typesFor('lease'))
                ->where('documentable_id', $lease->id)
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Document $document): array => $this->documentRows->present($document))
                ->values()
                ->all()
            : [];

        return [
            'leases' => $leases->map(fn (Lease $item): array => $this->leaseOption($item))->all(),
            'lease' => $lease ? $this->lease($lease) : null,
            'schedule' => $schedule->through(fn (LeaseInstallment $item): array => [
                'id' => $item->id,
                'sequence' => $item->sequence,
                'line_type' => $item->line_type,
                'label' => $item->label,
                'due_date' => $item->due_date?->toDateString(),
                'amount_due' => (float) $item->amount_due,
                'amount_paid' => (float) $item->amount_paid,
                'remaining' => $item->remaining_amount,
                'status' => $item->status,
            ]),
            'documents' => $documentRows,
        ];
    }

    /** @return array<string, mixed> */
    private function lease(Lease $lease): array
    {
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;

        return [
            ...$this->leaseOption($lease),
            'payment_frequency' => $lease->payment_frequency,
            'signed_at' => $lease->signed_at?->toDateString(),
            'rent_amount' => (float) $lease->rent_amount,
            'deposit_amount' => (float) $lease->deposit_amount,
            'currency' => $lease->currency,
            'billing_day' => $lease->billing_day,
            'total_due' => $lease->total_due,
            'total_paid' => $lease->total_paid,
            'balance_remaining' => $lease->balance_remaining,
            'due_now' => $lease->due_now_amount,
            'overdue' => $lease->overdue_amount,
            'days_remaining' => $lease->days_remaining,
            'next_due_date' => $lease->next_due_installment?->due_date?->toDateString(),
            'installment_count' => $lease->installments->count(),
            'asset' => $asset ? [
                'id' => $asset->id,
                'code' => $asset->code,
                'title_en' => $asset->title_en,
                'title_ar' => $asset->title_ar,
                'address' => $asset->address,
                'address_ar' => $asset->address_ar,
                'asset_type' => $asset->asset_type,
                'usage_type' => $asset->usage_type,
            ] : null,
            'contract_url' => route('leases.contract', $lease),
            'contract_word_url' => route('leases.contract.word', $lease),
            'statement_url' => route('leases.statement', $lease),
            'detail_url' => route('leases.show', $lease),
        ];
    }

    /** @return array<string, mixed> */
    private function leaseOption(Lease $lease): array
    {
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;

        return [
            'id' => $lease->id,
            'code' => $lease->code,
            'status' => $lease->status,
            'started_at' => $lease->started_at?->toDateString(),
            'ends_at' => $lease->ends_at?->toDateString(),
            'asset_title_en' => $asset?->title_en,
            'asset_title_ar' => $asset?->title_ar,
        ];
    }
}
