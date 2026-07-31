<?php

namespace App\Modules\Reports\Presenters;

use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Expenses\Support\ExpenseOptions;
use App\Modules\Reports\Data\PortfolioReportData;
use App\Modules\Shared\ResourcePresenter;
use Carbon\CarbonInterface;

final readonly class ReportJournalPresenter
{
    public function __construct(
        private ResourcePresenter $resources,
        private DocumentAttachments $attachments,
    ) {}

    /**
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     * @return array{journalSummary:array<string,int>,operationalJournal:array<int,array<string,mixed>>}
     */
    public function present(PortfolioReportData $data, array $filters, ?int $limit = 12): array
    {
        $leases = $data->leases->filter(
            fn (Lease $lease): bool => $lease->created_at !== null
                && $lease->created_at->toDateString() >= $filters['date_from']
                && $lease->created_at->toDateString() <= $filters['date_to'],
        );
        $events = collect()
            ->concat($data->payments->map(fn (Payment $payment) => $this->payment($payment)))
            ->concat($data->expenses->map(fn (ExpenseEntry $expense) => $this->expense($expense)))
            ->concat($leases->map(fn (Lease $lease) => $this->lease($lease)))
            ->concat($data->maintenanceRequests->map(
                fn (MaintenanceRequest $request) => $this->maintenance($request, false),
            ))
            ->concat($data->resolvedMaintenanceRequests->map(
                fn (MaintenanceRequest $request) => $this->maintenance($request, true),
            ))
            ->concat($data->documents->map(fn (Document $document) => $this->document($document)))
            ->sortByDesc('occurred_at')
            ->values();

        return [
            'journalSummary' => [
                'totalEvents' => $events->count(),
                'newLeases' => $leases->count(),
                'serviceOpened' => $data->maintenanceRequests->count(),
                'serviceResolved' => $data->resolvedMaintenanceRequests->count(),
                'documentsAdded' => $data->documents->count(),
            ],
            'operationalJournal' => ($limit === null ? $events : $events->take($limit))->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function payment(Payment $payment): array
    {
        return $this->event(
            $payment->received_on,
            'payment',
            $payment->reference ?: '#'.$payment->id,
            [$payment->tenantProfile?->user?->name, $payment->lease?->code],
            $payment->recordedBy?->name,
            route('payments.show', $payment),
            (float) $payment->amount,
            $payment->currency,
            'income',
            'bi-cash-stack',
            'success',
        );
    }

    /** @return array<string, mixed> */
    private function expense(ExpenseEntry $expense): array
    {
        return $this->event(
            $expense->incurred_on,
            'expense',
            $expense->title,
            [ExpenseOptions::label($expense->category), $this->assetName($expense->asset)],
            $expense->createdBy?->name,
            route('expenses.show', $expense),
            (float) $expense->amount,
            $expense->currency,
            'outflow',
            'bi-receipt',
            'warning',
        );
    }

    /** @return array<string, mixed> */
    private function lease(Lease $lease): array
    {
        return $this->event(
            $lease->created_at,
            'lease',
            $lease->code,
            [$lease->tenantProfile?->user?->name, $this->assetName($lease->leaseable)],
            $lease->managedBy?->name,
            route('leases.show', $lease),
            null,
            $lease->currency,
            'none',
            'bi-file-earmark-text',
            'info',
        );
    }

    /** @return array<string, mixed> */
    private function maintenance(MaintenanceRequest $request, bool $resolved): array
    {
        return $this->event(
            $resolved ? $request->resolved_at : ($request->requested_at ?? $request->created_at),
            $resolved ? 'maintenance_resolved' : 'maintenance_opened',
            $request->title,
            [$this->assetName($request->asset), $request->tenantProfile?->user?->name],
            ($resolved ? $request->resolvedBy : $request->submittedBy)?->name,
            route('maintenance-requests.show', $request),
            null,
            null,
            'none',
            $resolved ? 'bi-check2-circle' : 'bi-tools',
            $resolved ? 'success' : 'danger',
        );
    }

    /** @return array<string, mixed> */
    private function document(Document $document): array
    {
        $attachment = $this->attachments->present($document->documentable);

        return $this->event(
            $document->created_at,
            'document',
            $this->resources->localized($document->title_en, $document->title_ar)
                ?: $document->original_name,
            [DocumentOptions::label($document->type), $attachment['label'] ?? null],
            $document->uploadedBy?->name,
            route('documents.show', $document),
            null,
            null,
            'none',
            'bi-file-earmark-pdf',
            'info',
        );
    }

    /**
     * @param  array<int, string|null>  $context
     * @return array<string, mixed>
     */
    private function event(
        ?CarbonInterface $occurredAt,
        string $type,
        string $title,
        array $context,
        ?string $actor,
        string $href,
        ?float $amount,
        ?string $currency,
        string $direction,
        string $icon,
        string $tone,
    ): array {
        return [
            'key' => $type.'-'.$href,
            'type' => $type,
            'type_label' => trans("app.reports.journal_types.{$type}"),
            'title' => $title,
            'subtitle' => collect($context)->filter()->join(' · '),
            'occurred_at' => $occurredAt?->toIso8601String(),
            'actor' => $actor ?: trans('app.reports.journal_unknown_actor'),
            'href' => $href,
            'amount' => $amount,
            'currency' => $currency,
            'direction' => $direction,
            'icon' => $icon,
            'tone' => $tone,
        ];
    }

    private function assetName(mixed $asset): ?string
    {
        return $asset instanceof Asset
            ? $this->resources->localized($asset->title_en, $asset->title_ar) ?: $asset->code
            : null;
    }
}
