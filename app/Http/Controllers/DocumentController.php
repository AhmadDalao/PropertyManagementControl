<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->tableFilters($request, [
            'type' => 'all',
            'attachment' => 'all',
            'visibility' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);

        $baseQuery = $this->scopeByPortfolio(Document::query(), $actor);
        $documents = (clone $baseQuery)->with(['portfolio', 'uploadedBy', 'documentable']);

        $this->applyExactFilter($documents, $filters, 'portfolio_id');
        $this->applyExactFilter($documents, $filters, 'type');
        $this->applyAttachmentFilter($documents, (string) ($filters['attachment'] ?? 'all'));
        $this->applyVisibilityFilter($documents, (string) ($filters['visibility'] ?? 'all'));
        $this->applyDateRange($documents, $filters, 'created_at');
        $this->applyDocumentSearch($documents, $filters['search']);

        return Inertia::render('admin/documents/index', [
            'documents' => $this->paginateTable($documents, $request, $filters, [
                'created_at',
                'title_en',
                'type',
                'original_name',
                'file_size',
            ]),
            'filters' => $filters,
            'counts' => $this->documentCounts($baseQuery, $filters),
            'portfolioOptions' => $this->portfolioOptions($actor),
            'assetOptions' => $this->scopeByPortfolio(
                Asset::query()->orderBy('title_en'),
                $actor
            )->get(['id', 'portfolio_id', 'title_en', 'code']),
            'leaseOptions' => $this->scopeByPortfolio(
                Lease::query()->with('tenantProfile.user')->orderByDesc('created_at'),
                $actor
            )->get(['id', 'portfolio_id', 'tenant_profile_id', 'code']),
            'paymentOptions' => $this->scopeByPortfolio(
                Payment::query()->with(['lease', 'tenantProfile.user'])->orderByDesc('received_on'),
                $actor
            )->get(['id', 'portfolio_id', 'lease_id', 'tenant_profile_id', 'reference', 'amount', 'currency']),
        ]);
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->documentFormPage($actor),
        ]);
    }

    public function show(Request $request, Document $document): Response
    {
        $actor = $this->actor($request);
        $this->ensureDocumentManagementAccess($actor, $document);
        $document->loadMissing(['portfolio', 'uploadedBy', 'documentable']);

        return Inertia::render('admin/resource-show', [
            'detailPage' => [
                'header' => [
                    'eyebrow' => 'Document detail',
                    'title' => $document->title_en ?: $document->original_name,
                    'description' => trim($document->type.' · '.$document->original_name),
                    'backHref' => route('documents.index'),
                    'backLabel' => 'All documents',
                    'actions' => [
                        ['label' => 'Edit document', 'href' => route('documents.edit', $document), 'variant' => 'primary'],
                        ['label' => 'Download', 'href' => route('documents.download', $document), 'variant' => 'secondary'],
                    ],
                ],
                'stats' => $this->detailItems([
                    ['label' => 'Type', 'value' => $document->type, 'tone' => 'primary'],
                    ['label' => 'Visibility', 'value' => $document->is_public ? 'Public' : 'Private'],
                    ['label' => 'Size', 'value' => number_format((float) $document->file_size / 1024, 1).' KB'],
                    ['label' => 'MIME', 'value' => $document->mime_type],
                ]),
                'sections' => [
                    [
                        'title' => 'File record',
                        'description' => 'Stored file, attachment target, and uploader.',
                        'items' => $this->detailItems([
                            ['label' => 'Arabic title', 'value' => $document->title_ar],
                            ['label' => 'Original name', 'value' => $document->original_name],
                            ['label' => 'Portfolio', 'value' => $document->portfolio?->name_en, 'href' => $document->portfolio ? route('portfolios.show', $document->portfolio) : null],
                            ['label' => 'Uploaded by', 'value' => $document->uploadedBy?->name, 'href' => $document->uploadedBy ? route('users.show', $document->uploadedBy) : null],
                            ['label' => 'Attachment type', 'value' => $document->documentable_type],
                            ['label' => 'Attachment ID', 'value' => $document->documentable_id],
                            ['label' => 'Disk', 'value' => $document->disk],
                            ['label' => 'Path', 'value' => $document->file_path],
                        ]),
                    ],
                ],
                'related' => [],
                'documents' => [[
                    'id' => $document->id,
                    'title' => $document->title_en ?: $document->original_name,
                    'subtitle' => $document->original_name,
                    'badge' => $document->is_public ? 'Public' : 'Private',
                    'href' => route('documents.download', $document),
                ]],
                'timeline' => $this->activityTimeline($document),
            ],
        ]);
    }

    public function edit(Request $request, Document $document): Response
    {
        $actor = $this->actor($request);
        $this->ensureDocumentManagementAccess($actor, $document);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->documentFormPage($actor, $document),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'documentable_type' => ['required', 'string', Rule::in(array_keys($this->documentableTypes()))],
            'documentable_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'max:80'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        [$documentableAlias, $documentable] = $this->resolveDocumentable(
            $data['documentable_type'],
            (int) $data['documentable_id'],
        );
        $portfolioId = $data['portfolio_id'] ?? (int) $documentable->getAttribute('portfolio_id');

        $this->ensurePortfolioAccess($actor, $portfolioId);
        abort_if(
            (int) $documentable->getAttribute('portfolio_id') !== (int) $portfolioId,
            422,
            'Selected record does not belong to the selected portfolio.'
        );

        $file = $data['file'];
        $path = $file->store("documents/library/{$portfolioId}", 'local');

        $document = Document::query()->create([
            'portfolio_id' => $portfolioId,
            'uploaded_by_user_id' => $actor->id,
            'documentable_type' => $documentableAlias,
            'documentable_id' => $documentable->getKey(),
            'type' => $data['type'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'] ?? null,
            'disk' => 'local',
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'is_public' => (bool) ($data['is_public'] ?? false),
        ]);

        return to_route('documents.show', $document)->with('success', 'Document uploaded successfully.');
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->ensureDocumentManagementAccess($actor, $document);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'documentable_type' => ['required', 'string', Rule::in(array_keys($this->documentableTypes()))],
            'documentable_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'max:80'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        [$documentableAlias, $documentable] = $this->resolveDocumentable(
            $data['documentable_type'],
            (int) $data['documentable_id'],
        );
        $portfolioId = $data['portfolio_id'] ?? (int) $documentable->getAttribute('portfolio_id');

        $this->ensurePortfolioAccess($actor, $portfolioId);
        abort_if(
            (int) $documentable->getAttribute('portfolio_id') !== (int) $portfolioId,
            422,
            'Selected record does not belong to the selected portfolio.'
        );

        $document->update([
            'portfolio_id' => $portfolioId,
            'documentable_type' => $documentableAlias,
            'documentable_id' => $documentable->getKey(),
            'type' => $data['type'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'] ?? null,
            'is_public' => (bool) ($data['is_public'] ?? false),
        ]);

        return to_route('documents.show', $document)->with('success', 'Document details updated successfully.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $actor = $this->actor(request());
        $this->ensureDocumentManagementAccess($actor, $document);

        Storage::disk($document->disk)->delete($document->file_path);
        $document->delete();

        return to_route('documents.index')->with('success', 'Document deleted successfully.');
    }

    public function download(Document $document): StreamedResponse
    {
        /** @var User $actor */
        $actor = request()->user();

        abort_unless($this->canDownload($actor, $document), 403);

        return Storage::disk($document->disk)->download($document->file_path, $document->original_name);
    }

    private function documentFormPage(User $actor, ?Document $document = null): array
    {
        $fields = [];

        if ($actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'options' => collect($this->portfolioOptions($actor))
                    ->map(fn ($portfolio) => ['value' => $portfolio['id'], 'label' => $portfolio['name']])
                    ->prepend(['value' => '', 'label' => 'Use attached record portfolio'])
                    ->values()
                    ->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'documentable_type', 'label' => 'Attach to', 'type' => 'select', 'required' => true, 'options' => $this->fieldOptions(['lease', 'asset', 'payment'])],
            ['name' => 'documentable_id', 'label' => 'Attached record ID', 'type' => 'number', 'required' => true, 'help' => 'Use the ID from the lease, asset, or payment detail URL.'],
            ['name' => 'type', 'label' => 'Document type', 'required' => true, 'placeholder' => 'lease_contract, signed_contract, receipt'],
            ['name' => 'title_en', 'label' => 'English title', 'required' => true],
            ['name' => 'title_ar', 'label' => 'Arabic title'],
            ['name' => 'is_public', 'label' => 'Visible to tenant when allowed', 'type' => 'checkbox'],
        ];

        if ($document === null) {
            $fields[] = [
                'name' => 'file',
                'label' => 'PDF file',
                'type' => 'file',
                'required' => true,
                'accept' => '.pdf,application/pdf',
                'help' => 'Contracts, signed papers, receipts, and proof files must be uploaded as PDF. Maximum upload size: 10 MB.',
            ];
        }

        return [
            'title' => $document ? 'Edit document' : 'Upload document',
            'description' => 'Attach contracts, receipts, signed papers, and proof files to operational records.',
            'backHref' => $document ? route('documents.show', $document) : route('documents.index'),
            'backLabel' => $document ? 'Document detail' : 'All documents',
            'action' => $document ? route('documents.update', $document) : route('documents.store'),
            'method' => $document ? 'put' : 'post',
            'submitLabel' => $document ? 'Update document' : 'Upload document',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) ($document?->portfolio_id ?? request('portfolio_id', $actor->portfolio_id ?? '')),
                'documentable_type' => (string) request('documentable_type', $document ? $this->documentableAlias($this->documentableTypes()[$document->documentable_type] ?? $document->documentable_type) : 'lease'),
                'documentable_id' => (string) request('documentable_id', $document?->documentable_id ?? ''),
                'type' => $document?->type ?? '',
                'title_en' => $document?->title_en ?? '',
                'title_ar' => $document?->title_ar ?? '',
                'is_public' => (bool) ($document?->is_public ?? false),
                'file' => null,
            ],
        ];
    }

    private function ensureDocumentManagementAccess(User $actor, Document $document): void
    {
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $document->portfolio_id);
    }

    private function canDownload(User $actor, Document $document): bool
    {
        if ($actor->hasRole('superadmin')) {
            return true;
        }

        if ($actor->hasAnyRole(['owner', 'property_manager']) && $actor->portfolio_id === $document->portfolio_id) {
            return true;
        }

        if ($actor->hasRole('tenant') && $this->documentMatchesType($document, 'lease')) {
            $lease = $document->documentable;

            return $lease?->tenantProfile?->user_id === $actor->id;
        }

        return false;
    }

    /**
     * @return array<string, class-string<Model>>
     */
    private function documentableTypes(): array
    {
        return [
            'lease' => Lease::class,
            Lease::class => Lease::class,
            'asset' => Asset::class,
            Asset::class => Asset::class,
            'payment' => Payment::class,
            Payment::class => Payment::class,
        ];
    }

    /**
     * @return array{0:string,1:Model}
     */
    private function resolveDocumentable(string $type, int $id): array
    {
        $class = $this->documentableTypes()[$type] ?? null;
        abort_if($class === null, 422, 'Unsupported document attachment type.');

        $documentable = $class::query()->findOrFail($id);

        return [$this->documentableAlias($class), $documentable];
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function documentableAlias(string $class): string
    {
        return match ($class) {
            Lease::class => 'lease',
            Asset::class => 'asset',
            Payment::class => 'payment',
            default => $class,
        };
    }

    /**
     * @return array<int, string>
     */
    private function documentableTypeValues(string $alias): array
    {
        $class = $this->documentableTypes()[$alias] ?? null;

        if (! $class) {
            return [$alias];
        }

        return [$this->documentableAlias($class), $class];
    }

    private function documentMatchesType(Document $document, string $alias): bool
    {
        return in_array($document->documentable_type, $this->documentableTypeValues($alias), true);
    }

    private function applyAttachmentFilter(Builder $documents, string $attachment): void
    {
        if ($attachment === 'all' || $attachment === '') {
            return;
        }

        $documents->whereIn('documentable_type', $this->documentableTypeValues($attachment));
    }

    private function applyVisibilityFilter(Builder $documents, string $visibility): void
    {
        if ($visibility === 'public') {
            $documents->where('is_public', true);
        }

        if ($visibility === 'private') {
            $documents->where('is_public', false);
        }
    }

    private function applyDocumentSearch(Builder $documents, string $search): void
    {
        $this->applySearch($documents, $search, [
            'title_en',
            'title_ar',
            'original_name',
            'type',
            'file_path',
            fn (Builder $query, string $term, string $like) => $query
                ->orWhere(function (Builder $subQuery) use ($like): void {
                    $subQuery
                        ->whereIn('documentable_type', $this->documentableTypeValues('lease'))
                        ->whereIn('documentable_id', Lease::query()->select('id')->where('code', 'like', $like));
                })
                ->orWhere(function (Builder $subQuery) use ($like): void {
                    $subQuery
                        ->whereIn('documentable_type', $this->documentableTypeValues('asset'))
                        ->whereIn(
                            'documentable_id',
                            Asset::query()
                                ->select('id')
                                ->where('title_en', 'like', $like)
                                ->orWhere('title_ar', 'like', $like)
                                ->orWhere('code', 'like', $like)
                        );
                })
                ->orWhere(function (Builder $subQuery) use ($like): void {
                    $subQuery
                        ->whereIn('documentable_type', $this->documentableTypeValues('payment'))
                        ->whereIn('documentable_id', Payment::query()->select('id')->where('reference', 'like', $like));
                }),
        ]);
    }

    /**
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    private function documentCounts(Builder $baseQuery, array $filters): array
    {
        $activeType = (string) ($filters['type'] ?? 'all');
        $activeVisibility = (string) ($filters['visibility'] ?? 'all');

        return [
            [
                'label' => 'All',
                'value' => (clone $baseQuery)->count(),
                'filter' => ['type' => 'all', 'visibility' => 'all'],
                'active' => $activeType === 'all' && $activeVisibility === 'all',
            ],
            [
                'label' => 'Lease contracts',
                'value' => (clone $baseQuery)->where('type', 'lease_contract')->count(),
                'filter' => ['type' => 'lease_contract'],
                'active' => $activeType === 'lease_contract',
            ],
            [
                'label' => 'Signed',
                'value' => (clone $baseQuery)->where('type', 'signed_contract')->count(),
                'filter' => ['type' => 'signed_contract'],
                'active' => $activeType === 'signed_contract',
            ],
            [
                'label' => 'Receipts',
                'value' => (clone $baseQuery)->where('type', 'receipt')->count(),
                'filter' => ['type' => 'receipt'],
                'active' => $activeType === 'receipt',
            ],
            [
                'label' => 'Public',
                'value' => (clone $baseQuery)->where('is_public', true)->count(),
                'filter' => ['visibility' => 'public'],
                'active' => $activeVisibility === 'public',
            ],
        ];
    }
}
