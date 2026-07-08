<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaFileController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/media/index', [
            'mediaFiles' => $this->scopeByPortfolio(MediaFile::query()->latest(), $actor)->get(),
            'portfolioOptions' => $this->portfolioOptions($actor),
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

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        if ($portfolioId) {
            $this->ensurePortfolioAccess($actor, $portfolioId);
        }

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

    public function destroy(MediaFile $mediaFile): RedirectResponse
    {
        $actor = $this->actor(request());

        if ($mediaFile->portfolio_id) {
            $this->ensurePortfolioAccess($actor, $mediaFile->portfolio_id);
        } else {
            $this->requireRoles($actor, ['superadmin']);
        }

        \Storage::disk($mediaFile->disk)->delete($mediaFile->path);
        $mediaFile->delete();

        return to_route('media-files.index')->with('success', 'Media deleted successfully.');
    }
}
