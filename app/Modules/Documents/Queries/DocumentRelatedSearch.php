<?php

namespace App\Modules\Documents\Queries;

use App\Models\Asset;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\Payment;
use App\Modules\Documents\Support\DocumentAttachments;
use Illuminate\Database\Eloquent\Builder;

final class DocumentRelatedSearch
{
    public function __construct(private readonly DocumentAttachments $attachments) {}

    /**
     * @param  Builder<Document>  $documents
     * @return Builder<Document>
     */
    public function apply(Builder $documents, string $like): Builder
    {
        return $documents
            ->orWhereHas('uploadedBy', fn (Builder $users) => $users
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like))
            ->orWhere(fn (Builder $leases) => $leases
                ->whereIn('documentable_type', $this->attachments->typesFor('lease'))
                ->whereIn('documentable_id', Lease::query()->select('id')->where('code', 'like', $like)))
            ->orWhere(fn (Builder $assets) => $assets
                ->whereIn('documentable_type', $this->attachments->typesFor('asset'))
                ->whereIn('documentable_id', Asset::query()
                    ->select('id')
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like)))
            ->orWhere(fn (Builder $payments) => $payments
                ->whereIn('documentable_type', $this->attachments->typesFor('payment'))
                ->whereIn('documentable_id', Payment::query()->select('id')->where('reference', 'like', $like)))
            ->orWhere(fn (Builder $expenses) => $expenses
                ->whereIn('documentable_type', $this->attachments->typesFor('expense'))
                ->whereIn('documentable_id', ExpenseEntry::query()
                    ->select('id')
                    ->where('title', 'like', $like)
                    ->orWhere('vendor_name', 'like', $like)
                    ->orWhere('category', 'like', $like)));
    }
}
