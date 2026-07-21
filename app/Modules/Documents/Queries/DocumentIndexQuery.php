<?php

namespace App\Modules\Documents\Queries;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentIndexQuery
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentAttachments $attachments,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $this->access->ensureManager($actor);
        $filters = $this->filters($request);
        $baseQuery = $this->portfolios->apply(Document::query(), $actor);
        $documents = (clone $baseQuery)->with(['portfolio', 'uploadedBy', 'documentable']);
        $this->applyFilters($documents, $filters);
        $metricScope = clone $baseQuery;
        $this->tables->exact($metricScope, $filters, 'portfolio_id');

        return [
            'documents' => $this->paginate($documents, $filters),
            'documentInsights' => $this->insights($metricScope),
            'filters' => $filters,
            'counts' => $this->counts($metricScope, $filters),
            'portfolioOptions' => $this->portfolios->options($actor),
            'typeOptions' => DocumentOptions::TYPES,
            'attachmentOptions' => DocumentOptions::ATTACHMENTS,
            'visibilityOptions' => DocumentOptions::VISIBILITIES,
        ];
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $this->tables->filters($request, [
            'type' => 'all',
            'attachment' => 'all',
            'visibility' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);
    }

    /**
     * @param  Builder<Document>  $documents
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $documents, array $filters): void
    {
        $this->tables->exact($documents, $filters, 'portfolio_id');
        $this->tables->exact($documents, $filters, 'type');
        $this->tables->dateRange($documents, $filters, 'created_at');

        if (! empty($filters['attachment']) && $filters['attachment'] !== 'all') {
            $documents->whereIn(
                'documentable_type',
                $this->attachments->typesFor((string) $filters['attachment']),
            );
        }

        if (($filters['visibility'] ?? 'all') === 'public') {
            $documents->where('is_public', true);
        }

        if (($filters['visibility'] ?? 'all') === 'private') {
            $documents->where('is_public', false);
        }

        $this->tables->search($documents, (string) $filters['search'], [
            'title_en',
            'title_ar',
            'original_name',
            'type',
            fn (Builder $query, string $search, string $like) => $query
                ->orWhereHas('uploadedBy', fn (Builder $userQuery) => $userQuery
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like))
                ->orWhere(fn (Builder $subQuery) => $subQuery
                    ->whereIn('documentable_type', $this->attachments->typesFor('lease'))
                    ->whereIn('documentable_id', Lease::query()->select('id')->where('code', 'like', $like)))
                ->orWhere(fn (Builder $subQuery) => $subQuery
                    ->whereIn('documentable_type', $this->attachments->typesFor('asset'))
                    ->whereIn('documentable_id', Asset::query()
                        ->select('id')
                        ->where('title_en', 'like', $like)
                        ->orWhere('title_ar', 'like', $like)
                        ->orWhere('code', 'like', $like)))
                ->orWhere(fn (Builder $subQuery) => $subQuery
                    ->whereIn('documentable_type', $this->attachments->typesFor('payment'))
                    ->whereIn('documentable_id', Payment::query()->select('id')->where('reference', 'like', $like))),
        ]);
    }

    /**
     * @param  Builder<Document>  $query
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginate(Builder $query, array $filters): LengthAwarePaginator
    {
        return $this->tables->paginate($query, $filters, [
            'created_at',
            'title_en',
            'type',
            'original_name',
            'file_size',
        ])->through(fn (Document $document) => $this->tableRow($document));
    }

    /** @return array<string, mixed> */
    private function tableRow(Document $document): array
    {
        $document->loadMissing(['portfolio', 'uploadedBy', 'documentable']);
        $attachment = $this->attachments->present(
            $document->documentable instanceof Model ? $document->documentable : null,
        );

        return [
            'id' => $document->id,
            'type' => $document->type,
            'title_en' => $document->title_en,
            'title_ar' => $document->title_ar,
            'original_name' => $document->original_name,
            'file_size' => $document->file_size,
            'is_public' => $document->is_public,
            'created_at' => $document->created_at?->toDateTimeString(),
            'download_url' => route('documents.download', $document),
            'attachment' => $attachment ?? [
                'type' => $this->attachments->aliasForDocument($document) ?? 'record',
                'label' => '#'.$document->documentable_id,
                'url' => null,
            ],
            'portfolio' => [
                'name_en' => $document->portfolio?->name_en,
                'name_ar' => $document->portfolio?->name_ar,
            ],
            'uploaded_by' => ['name' => $document->uploadedBy?->name],
        ];
    }

    /**
     * @param  Builder<Document>  $baseQuery
     * @return array<string, int>
     */
    private function insights(Builder $baseQuery): array
    {
        return [
            'total' => (clone $baseQuery)->count(),
            'contracts' => (clone $baseQuery)->whereIn('type', ['lease_contract', 'signed_contract'])->count(),
            'signed' => (clone $baseQuery)->where('type', 'signed_contract')->count(),
            'receipts' => (clone $baseQuery)->where('type', 'receipt')->count(),
            'portal_visible' => (clone $baseQuery)->where('is_public', true)->count(),
        ];
    }

    /**
     * @param  Builder<Document>  $baseQuery
     * @param  array<string, mixed>  $filters
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    private function counts(Builder $baseQuery, array $filters): array
    {
        $activeType = (string) ($filters['type'] ?? 'all');
        $activeVisibility = (string) ($filters['visibility'] ?? 'all');

        return [
            ['label' => trans('app.documents.all'), 'value' => (clone $baseQuery)->count(), 'filter' => ['type' => 'all', 'visibility' => 'all'], 'active' => $activeType === 'all' && $activeVisibility === 'all'],
            ['label' => trans('app.documents.count_lease_contracts'), 'value' => (clone $baseQuery)->where('type', 'lease_contract')->count(), 'filter' => ['type' => 'lease_contract'], 'active' => $activeType === 'lease_contract'],
            ['label' => trans('app.documents.count_signed'), 'value' => (clone $baseQuery)->where('type', 'signed_contract')->count(), 'filter' => ['type' => 'signed_contract'], 'active' => $activeType === 'signed_contract'],
            ['label' => trans('app.documents.receipts'), 'value' => (clone $baseQuery)->where('type', 'receipt')->count(), 'filter' => ['type' => 'receipt'], 'active' => $activeType === 'receipt'],
            ['label' => trans('app.documents.portal_visible'), 'value' => (clone $baseQuery)->where('is_public', true)->count(), 'filter' => ['visibility' => 'public'], 'active' => $activeVisibility === 'public'],
        ];
    }
}
