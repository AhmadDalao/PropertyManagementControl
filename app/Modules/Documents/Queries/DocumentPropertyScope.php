<?php

namespace App\Modules\Documents\Queries;

use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Assets\Support\PropertyScope;
use App\Modules\Documents\Support\DocumentAttachments;
use Illuminate\Database\Eloquent\Builder;

final readonly class DocumentPropertyScope
{
    public function __construct(
        private PropertyScope $properties,
        private DocumentAttachments $attachments,
    ) {}

    /**
     * @param  Builder<Document>  $documents
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $documents, array $filters, User $actor): void
    {
        $assetIds = $this->properties->assetIds(
            $actor,
            $filters['portfolio_id'],
            $filters['property_id'],
        );

        if ($assetIds === null) {
            return;
        }

        $leaseIds = fn (): Builder => Lease::query()
            ->select('id')
            ->whereIn('leaseable_type', $this->properties->leaseableTypes())
            ->whereIn('leaseable_id', $assetIds);

        $maintenanceIds = fn (): Builder => MaintenanceRequest::query()
            ->select('id')
            ->whereIn('asset_id', $assetIds);

        $expenseIds = fn (): Builder => ExpenseEntry::query()
            ->select('id')
            ->where(function (Builder $expenses) use ($assetIds, $leaseIds, $maintenanceIds): void {
                $expenses
                    ->whereIn('asset_id', $assetIds)
                    ->orWhereIn('lease_id', $leaseIds())
                    ->orWhereIn('maintenance_request_id', $maintenanceIds());
            });

        $documents->where(function (Builder $query) use ($assetIds, $leaseIds, $expenseIds): void {
            $query
                ->where(function (Builder $assets) use ($assetIds): void {
                    $assets
                        ->whereIn('documentable_type', $this->attachments->typesFor('asset'))
                        ->whereIn('documentable_id', $assetIds);
                })
                ->orWhere(function (Builder $leases) use ($leaseIds): void {
                    $leases
                        ->whereIn('documentable_type', $this->attachments->typesFor('lease'))
                        ->whereIn('documentable_id', $leaseIds());
                })
                ->orWhere(function (Builder $payments) use ($leaseIds): void {
                    $payments
                        ->whereIn('documentable_type', $this->attachments->typesFor('payment'))
                        ->whereIn(
                            'documentable_id',
                            Payment::query()
                                ->select('id')
                                ->whereIn('lease_id', $leaseIds()),
                        );
                })
                ->orWhere(function (Builder $expenses) use ($expenseIds): void {
                    $expenses
                        ->whereIn('documentable_type', $this->attachments->typesFor('expense'))
                        ->whereIn('documentable_id', $expenseIds());
                });
        });
    }
}
