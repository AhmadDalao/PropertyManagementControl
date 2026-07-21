<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Modules\Documents\Actions\DocumentDownloads;
use App\Modules\Documents\Actions\ManageDocuments;
use App\Modules\Documents\Presenters\DocumentDetailPresenter;
use App\Modules\Documents\Presenters\DocumentFormPresenter;
use App\Modules\Documents\Queries\DocumentIndexQuery;
use App\Modules\Documents\Requests\StoreDocumentRequest;
use App\Modules\Documents\Requests\UpdateDocumentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentIndexQuery $indexQuery,
        private readonly DocumentFormPresenter $formPresenter,
        private readonly DocumentDetailPresenter $detailPresenter,
        private readonly ManageDocuments $documents,
        private readonly DocumentDownloads $downloads,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/documents/index',
            $this->indexQuery->handle($request, $this->actor($request)),
        );
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present(
                $this->actor($request),
                defaults: $request->only([
                    'documentable_type',
                    'documentable_id',
                    'type',
                    'title_en',
                    'title_ar',
                ]),
            ),
        ]);
    }

    public function show(Request $request, Document $document): Response
    {
        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->detailPresenter->present($document, $this->actor($request)),
        ]);
    }

    public function edit(Request $request, Document $document): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($this->actor($request), $document),
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $document = $this->documents->create($this->actor($request), $request->validated());

        return to_route('documents.show', $document)
            ->with('success', trans('app.messages.document_uploaded'));
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->documents->update($this->actor($request), $document, $request->validated());

        return to_route('documents.show', $document)
            ->with('success', trans('app.messages.document_updated'));
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $this->documents->delete($this->actor($request), $document);

        return to_route('documents.index')
            ->with('success', trans('app.messages.document_deleted'));
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        return $this->downloads->download($this->actor($request), $document);
    }
}
