<?php

namespace App\Http\Controllers;

use App\Modules\OpeningData\Actions\CommitOpeningData;
use App\Modules\OpeningData\Actions\OpeningDataTemplate;
use App\Modules\OpeningData\Actions\PreviewOpeningData;
use App\Modules\OpeningData\Presenters\OpeningDataPagePresenter;
use App\Modules\OpeningData\Requests\CommitOpeningDataRequest;
use App\Modules\OpeningData\Requests\PreviewOpeningDataRequest;
use App\Modules\OpeningData\Support\OpeningDataPreviewStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class OpeningDataController extends Controller
{
    public function __construct(
        private readonly OpeningDataPagePresenter $page,
        private readonly OpeningDataTemplate $template,
        private readonly PreviewOpeningData $preview,
        private readonly CommitOpeningData $commit,
        private readonly OpeningDataPreviewStore $previews,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/opening-data/index',
            $this->page->present(
                $this->actor($request),
                $request->string('preview')->toString() ?: null,
            ),
        );
    }

    public function template(Request $request): BinaryFileResponse
    {
        $path = $this->template->create($this->actor($request));

        return response()
            ->download(
                $path,
                'property-opening-data-template.xlsx',
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
            )
            ->deleteFileAfterSend(true);
    }

    public function preview(PreviewOpeningDataRequest $request): RedirectResponse
    {
        $token = $this->preview->handle(
            $this->actor($request),
            (int) $request->validated('portfolio_id'),
            $request->file('file'),
        );

        return to_route('opening-data.index', ['preview' => $token]);
    }

    public function store(CommitOpeningDataRequest $request): RedirectResponse
    {
        $counts = $this->commit->handle(
            $this->actor($request),
            (string) $request->validated('preview_token'),
        );

        return to_route('opening-data.index')->with(
            'success',
            trans('app.opening_data.import_complete', [
                'assets' => $counts['assets'],
                'tenants' => $counts['tenants'],
                'leases' => $counts['leases'],
                'payments' => $counts['payments'],
            ]),
        );
    }

    public function destroyPreview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preview_token' => ['required', 'string', 'size:48', 'regex:/^[A-Za-z0-9]+$/'],
        ]);
        $this->previews->delete($this->actor($request), $validated['preview_token']);

        return to_route('opening-data.index');
    }
}
