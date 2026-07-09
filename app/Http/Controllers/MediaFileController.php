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

        MediaFile::query()->create([
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

        return to_route('media-files.index')->with('success', 'Media uploaded successfully.');
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

        return to_route('media-files.index')->with('success', 'Media details updated successfully.');
    }

    public function destroy(MediaFile $mediaFile): RedirectResponse
    {
        $actor = $this->actor(request());
        $this->ensureMediaAccess($actor, $mediaFile);

        Storage::disk($mediaFile->disk)->delete($mediaFile->path);
        $mediaFile->delete();

        return to_route('media-files.index')->with('success', 'Media deleted successfully.');
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
