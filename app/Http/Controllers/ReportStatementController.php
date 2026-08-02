<?php

namespace App\Http\Controllers;

use App\Modules\Reports\Actions\OwnerStatementPdfExport;
use App\Modules\Reports\Actions\OwnerStatementWordExport;
use App\Modules\Reports\Actions\OwnerStatementWorkbookExport;
use App\Modules\Reports\Presenters\OwnerStatementPresenter;
use App\Modules\Reports\Requests\ReportIndexRequest;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportStatementController extends Controller
{
    public function __construct(
        private readonly OwnerStatementPresenter $statements,
        private readonly OwnerStatementPdfExport $pdfExport,
        private readonly OwnerStatementWordExport $wordExport,
        private readonly OwnerStatementWorkbookExport $workbookExport,
    ) {}

    public function show(ReportIndexRequest $request): Response
    {
        return Inertia::render(
            'admin/reports/statement',
            $this->statements->present($this->actor($request), $request->filters()),
        );
    }

    public function pdf(ReportIndexRequest $request): StreamedResponse
    {
        return $this->pdfExport->download(
            $this->statements->present($this->actor($request), $request->filters()),
        );
    }

    public function word(ReportIndexRequest $request): StreamedResponse
    {
        return $this->wordExport->download(
            $this->statements->present($this->actor($request), $request->filters()),
        );
    }

    public function workbook(ReportIndexRequest $request): BinaryFileResponse
    {
        $filters = $request->filters();
        $actor = $this->actor($request);

        return $this->workbookExport->download(
            $this->statements->present($actor, $filters),
            $filters,
            $actor,
        );
    }
}
