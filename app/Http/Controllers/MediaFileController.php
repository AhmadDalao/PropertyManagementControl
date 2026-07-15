<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MediaFileController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->tableFilters($request, [
            'visibility' => 'all',
            'collection' => 'all',
        ]);
        $baseQuery = $this->scopeByPortfolio(MediaFile::query(), $actor);
        $mediaFiles = (clone $baseQuery)->with(['portfolio', 'uploadedBy']);

        $this->applyExactFilter($mediaFiles, $filters, 'portfolio_id');
        $this->applyExactFilter($mediaFiles, $filters, 'visibility');
        $this->applyExactFilter($mediaFiles, $filters, 'collection');
        $this->applySearch($mediaFiles, $filters['search'], [
            'title_en',
            'title_ar',
            'alt_text_en',
            'alt_text_ar',
            'collection',
            'path',
            'mime_type',
        ]);

        return Inertia::render('admin/media/index', [
            'mediaFiles' => $this->paginateTable($mediaFiles, $request, $filters, [
                'created_at',
                'title_en',
                'collection',
                'visibility',
                'size',
            ]),
            'filters' => $filters,
            'counts' => [
                [
                    'label' => 'All',
                    'value' => (clone $baseQuery)->count(),
                    'filter' => ['visibility' => 'all'],
                    'active' => ($filters['visibility'] ?? 'all') === 'all',
                ],
                [
                    'label' => 'Public',
                    'value' => (clone $baseQuery)->where('visibility', 'public')->count(),
                    'filter' => ['visibility' => 'public'],
                    'active' => ($filters['visibility'] ?? 'all') === 'public',
                ],
                [
                    'label' => 'Private',
                    'value' => (clone $baseQuery)->where('visibility', 'private')->count(),
                    'filter' => ['visibility' => 'private'],
                    'active' => ($filters['visibility'] ?? 'all') === 'private',
                ],
            ],
            'portfolioOptions' => $this->portfolioOptions($actor),
            'collectionOptions' => (clone $baseQuery)
                ->whereNotNull('collection')
                ->distinct()
                ->orderBy('collection')
                ->pluck('collection')
                ->values(),
        ]);
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->mediaFormPage($actor),
        ]);
    }

    public function show(Request $request, MediaFile $mediaFile): Response
    {
        $actor = $this->actor($request);
        $this->ensureMediaAccess($actor, $mediaFile);
        $mediaFile->loadMissing(['portfolio', 'uploadedBy']);

        return Inertia::render('admin/resource-show', [
            'detailPage' => [
                'header' => [
                    'eyebrow' => 'Media detail',
                    'title' => $mediaFile->title_en ?: basename($mediaFile->path),
                    'description' => trim($mediaFile->collection.' · '.$mediaFile->visibility.' · '.$mediaFile->mime_type),
                    'backHref' => route('media-files.index'),
                    'backLabel' => 'All media',
                    'actions' => [
                        ['label' => 'Edit media', 'href' => route('media-files.edit', $mediaFile), 'variant' => 'primary'],
                    ],
                ],
                'stats' => $this->detailItems([
                    ['label' => 'Collection', 'value' => $mediaFile->collection, 'tone' => 'primary'],
                    ['label' => 'Visibility', 'value' => $mediaFile->visibility],
                    ['label' => 'Size', 'value' => number_format((float) $mediaFile->size / 1024, 1).' KB'],
                    ['label' => 'MIME', 'value' => $mediaFile->mime_type],
                ]),
                'sections' => [
                    [
                        'title' => 'Media record',
                        'description' => 'CMS and portal image metadata.',
                        'items' => $this->detailItems([
                            ['label' => 'Arabic title', 'value' => $mediaFile->title_ar],
                            ['label' => 'Alt text EN', 'value' => $mediaFile->alt_text_en],
                            ['label' => 'Alt text AR', 'value' => $mediaFile->alt_text_ar],
                            ['label' => 'Portfolio', 'value' => $mediaFile->portfolio?->name_en, 'href' => $mediaFile->portfolio ? route('portfolios.show', $mediaFile->portfolio) : null],
                            ['label' => 'Uploaded by', 'value' => $mediaFile->uploadedBy?->name, 'href' => $mediaFile->uploadedBy ? route('users.show', $mediaFile->uploadedBy) : null],
                            ['label' => 'Public URL', 'value' => $mediaFile->disk === 'public' ? Storage::disk('public')->url($mediaFile->path) : null],
                            ['label' => 'Path', 'value' => $mediaFile->path],
                        ]),
                    ],
                ],
                'related' => [],
                'documents' => [],
                'timeline' => $this->activityTimeline($mediaFile),
            ],
        ]);
    }

    public function edit(Request $request, MediaFile $mediaFile): Response
    {
        $actor = $this->actor($request);
        $this->ensureMediaAccess($actor, $mediaFile);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->mediaFormPage($actor, $mediaFile),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'collection' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'alt_text_en' => ['nullable', 'string', 'max:255'],
            'alt_text_ar' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', 'string'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $portfolioId = $request->has('portfolio_id') ? $data['portfolio_id'] ?? null : $actor->portfolio_id;
        $this->ensureMediaPortfolioAccess($actor, $portfolioId);

        $file = $data['file'];
        $path = $file->store('media', 'public');

        $mediaFile = MediaFile::query()->create([
            'uploaded_by_user_id' => $actor->id,
            'portfolio_id' => $portfolioId,
            'collection' => $data['collection'] ?? 'default',
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'title_en' => $data['title_en'] ?? null,
            'title_ar' => $data['title_ar'] ?? null,
            'alt_text_en' => $data['alt_text_en'] ?? null,
            'alt_text_ar' => $data['alt_text_ar'] ?? null,
            'visibility' => $data['visibility'],
        ]);

        return to_route('media-files.show', $mediaFile)->with('success', 'Media uploaded successfully.');
    }

    public function update(Request $request, MediaFile $mediaFile): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->ensureMediaAccess($actor, $mediaFile);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'collection' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'alt_text_en' => ['nullable', 'string', 'max:255'],
            'alt_text_ar' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', 'string'],
        ]);

        $portfolioId = $request->has('portfolio_id') ? $data['portfolio_id'] ?? null : $mediaFile->portfolio_id;
        $this->ensureMediaPortfolioAccess($actor, $portfolioId);

        $mediaFile->update([
            'portfolio_id' => $portfolioId,
            'collection' => $data['collection'] ?: 'default',
            'title_en' => $data['title_en'] ?? null,
            'title_ar' => $data['title_ar'] ?? null,
            'alt_text_en' => $data['alt_text_en'] ?? null,
            'alt_text_ar' => $data['alt_text_ar'] ?? null,
            'visibility' => $data['visibility'],
        ]);

        return to_route('media-files.show', $mediaFile)->with('success', 'Media details updated successfully.');
    }

    public function destroy(MediaFile $mediaFile): RedirectResponse
    {
        $actor = $this->actor(request());
        $this->ensureMediaAccess($actor, $mediaFile);

        Storage::disk($mediaFile->disk)->delete($mediaFile->path);
        $mediaFile->delete();

        return to_route('media-files.index')->with('success', 'Media deleted successfully.');
    }

    private function mediaFormPage(User $actor, ?MediaFile $mediaFile = null): array
    {
        $fields = [];

        if ($actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'options' => collect($this->portfolioOptions($actor))
                    ->map(fn ($portfolio) => ['value' => $portfolio['id'], 'label' => $portfolio['name']])
                    ->prepend(['value' => '', 'label' => 'Global CMS media'])
                    ->values()
                    ->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'collection', 'label' => 'Collection'],
            ['name' => 'title_en', 'label' => 'English title'],
            ['name' => 'title_ar', 'label' => 'Arabic title'],
            ['name' => 'alt_text_en', 'label' => 'English alt text'],
            ['name' => 'alt_text_ar', 'label' => 'Arabic alt text'],
            ['name' => 'visibility', 'label' => 'Visibility', 'type' => 'select', 'options' => $this->fieldOptions(['public', 'private'])],
        ];

        if ($mediaFile === null) {
            $fields[] = ['name' => 'file', 'label' => 'File', 'type' => 'file', 'required' => true];
        }

        return [
            'title' => $mediaFile ? 'Edit media' : 'Upload media',
            'description' => 'Upload images and files for CMS pages, landing sections, and portfolio content.',
            'backHref' => $mediaFile ? route('media-files.show', $mediaFile) : route('media-files.index'),
            'backLabel' => $mediaFile ? 'Media detail' : 'All media',
            'action' => $mediaFile ? route('media-files.update', $mediaFile) : route('media-files.store'),
            'method' => $mediaFile ? 'put' : 'post',
            'submitLabel' => $mediaFile ? 'Update media' : 'Upload media',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) ($mediaFile?->portfolio_id ?? request('portfolio_id', $actor->portfolio_id ?? '')),
                'collection' => $mediaFile?->collection ?? 'default',
                'title_en' => $mediaFile?->title_en ?? '',
                'title_ar' => $mediaFile?->title_ar ?? '',
                'alt_text_en' => $mediaFile?->alt_text_en ?? '',
                'alt_text_ar' => $mediaFile?->alt_text_ar ?? '',
                'visibility' => $mediaFile?->visibility ?? 'public',
                'file' => null,
            ],
        ];
    }

    private function ensureMediaAccess(User $actor, MediaFile $mediaFile): void
    {
        if ($mediaFile->portfolio_id) {
            $this->ensurePortfolioAccess($actor, $mediaFile->portfolio_id);

            return;
        }

        $this->requireRoles($actor, ['superadmin']);
    }

    private function ensureMediaPortfolioAccess(User $actor, ?int $portfolioId): void
    {
        if ($portfolioId === null) {
            $this->requireRoles($actor, ['superadmin']);

            return;
        }

        $this->ensurePortfolioAccess($actor, $portfolioId);
    }
}
