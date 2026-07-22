<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Modules\Media\Actions\ManageMediaFiles;
use App\Modules\Media\Actions\MediaFileResponse;
use App\Modules\Media\Presenters\MediaFileDetailPresenter;
use App\Modules\Media\Presenters\MediaFileFormPresenter;
use App\Modules\Media\Queries\MediaFileIndexQuery;
use App\Modules\Media\Requests\StoreMediaFileRequest;
use App\Modules\Media\Requests\UpdateMediaFileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaFileController extends Controller
{
    public function __construct(
        private readonly MediaFileIndexQuery $indexQuery,
        private readonly MediaFileFormPresenter $formPresenter,
        private readonly MediaFileDetailPresenter $detailPresenter,
        private readonly ManageMediaFiles $mediaFiles,
        private readonly MediaFileResponse $fileResponse,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/media/index',
            $this->indexQuery->handle($request, $this->actor($request)),
        );
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present(
                $this->actor($request),
                defaults: $request->only('portfolio_id'),
            ),
        ]);
    }

    public function show(Request $request, MediaFile $mediaFile): Response
    {
        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->detailPresenter->present($mediaFile, $this->actor($request)),
        ]);
    }

    public function edit(Request $request, MediaFile $mediaFile): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($this->actor($request), $mediaFile),
        ]);
    }

    public function store(StoreMediaFileRequest $request): RedirectResponse
    {
        $mediaFile = $this->mediaFiles->create($this->actor($request), $request->validated());

        return to_route('media-files.show', $mediaFile)
            ->with('success', trans('app.messages.media_uploaded'));
    }

    public function update(UpdateMediaFileRequest $request, MediaFile $mediaFile): RedirectResponse
    {
        $this->mediaFiles->update($this->actor($request), $mediaFile, $request->validated());

        return to_route('media-files.show', $mediaFile)
            ->with('success', trans('app.messages.media_updated'));
    }

    public function destroy(Request $request, MediaFile $mediaFile): RedirectResponse
    {
        $this->mediaFiles->delete($this->actor($request), $mediaFile);

        return to_route('media-files.index')
            ->with('success', trans('app.messages.media_deleted'));
    }

    public function file(Request $request, MediaFile $mediaFile): StreamedResponse
    {
        return $this->fileResponse->inline($this->actor($request), $mediaFile);
    }
}
