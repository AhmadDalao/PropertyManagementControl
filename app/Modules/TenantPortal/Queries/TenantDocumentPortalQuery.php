<?php

namespace App\Modules\TenantPortal\Queries;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Documents\Presenters\DocumentTableRowPresenter;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\TenantPortal\Support\TenantPortalAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class TenantDocumentPortalQuery
{
    public function __construct(
        private TenantPortalAccess $access,
        private DocumentAccess $documents,
        private DocumentAttachments $attachments,
        private DocumentTableRowPresenter $rows,
    ) {}

    /**
     * @param  array{search:string,status:string,type:string,lease_id:?int,date_from:string,date_to:string,per_page:int}  $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, array $filters): array
    {
        $profile = $this->access->profile($actor);
        /** @var Builder<Document> $base */
        $base = $this->documents->tenantPortalScope(Document::query(), $actor);
        $query = $this->filter(clone $base, $filters)
            ->with(['portfolio', 'uploadedBy', 'documentable'])
            ->latest('issued_on')
            ->latest('id');

        /** @var LengthAwarePaginator<int, Document> $documents */
        $documents = $query->paginate($filters['per_page'])->withQueryString();
        $documents->through(fn (Document $document): array => $this->rows->present($document));

        $typeCounts = collect(DocumentOptions::TYPES)
            ->mapWithKeys(fn (string $type): array => [
                $type => (clone $base)->where('type', $type)->count(),
            ])
            ->filter()
            ->all();
        /** @var Collection<int, Lease> $leases */
        $leases = $profile
            ? Lease::query()->where('tenant_profile_id', $profile->id)->with('leaseable')->latest('ends_at')->get()
            : new Collection;

        return [
            'filters' => $filters,
            'documents' => $documents,
            'counts' => ['all' => (clone $base)->count(), ...$typeCounts],
            'types' => array_keys($typeCounts),
            'leases' => $leases->map(fn (Lease $lease): array => $this->leaseOption($lease))->all(),
        ];
    }

    /**
     * @param  Builder<Document>  $query
     * @param  array{search:string,status:string,type:string,lease_id:?int,date_from:string,date_to:string,per_page:int}  $filters
     * @return Builder<Document>
     */
    private function filter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['type'] !== 'all', fn (Builder $copy) => $copy->where('type', $filters['type']))
            ->when($filters['date_from'] !== '', fn (Builder $copy) => $copy->whereDate('issued_on', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $copy) => $copy->whereDate('issued_on', '<=', $filters['date_to']))
            ->when($filters['lease_id'], fn (Builder $copy) => $this->lease($copy, (int) $filters['lease_id']))
            ->when($filters['search'] !== '', function (Builder $copy) use ($filters): void {
                $term = '%'.addcslashes($filters['search'], '%_\\').'%';
                $copy->where(function (Builder $search) use ($term): void {
                    $search
                        ->where('title_en', 'like', $term)
                        ->orWhere('title_ar', 'like', $term)
                        ->orWhere('original_name', 'like', $term)
                        ->orWhere('type', 'like', $term);
                });
            });
    }

    /** @param Builder<Document> $query */
    private function lease(Builder $query, int $leaseId): void
    {
        $query->where(function (Builder $documents) use ($leaseId): void {
            $documents
                ->where(function (Builder $leaseDocuments) use ($leaseId): void {
                    $leaseDocuments
                        ->whereIn('documentable_type', $this->attachments->typesFor('lease'))
                        ->where('documentable_id', $leaseId);
                })
                ->orWhere(function (Builder $paymentDocuments) use ($leaseId): void {
                    $paymentDocuments
                        ->whereIn('documentable_type', $this->attachments->typesFor('payment'))
                        ->whereIn('documentable_id', Payment::query()->select('id')->where('lease_id', $leaseId));
                });
        });
    }

    /** @return array{id:int,code:string,asset_title_en:?string,asset_title_ar:?string} */
    private function leaseOption(Lease $lease): array
    {
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;

        return [
            'id' => $lease->id,
            'code' => $lease->code,
            'asset_title_en' => $asset?->title_en,
            'asset_title_ar' => $asset?->title_ar,
        ];
    }
}
