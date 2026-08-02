<?php

namespace App\Http\Controllers;

use App\Modules\Reports\Actions\ArrearsAgingPdfExport;
use App\Modules\Reports\Actions\ArrearsAgingWordExport;
use App\Modules\Reports\Actions\ArrearsAgingWorkbookExport;
use App\Modules\Reports\Queries\ArrearsAgingQuery;
use App\Modules\Reports\Requests\ArrearsAgingRequest;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ArrearsAgingController extends Controller
{
    public function __construct(
        private readonly ArrearsAgingQuery $aging,
        private readonly ArrearsAgingPdfExport $pdfExport,
        private readonly ArrearsAgingWordExport $wordExport,
        private readonly ArrearsAgingWorkbookExport $workbookExport,
    ) {}

    public function index(ArrearsAgingRequest $request): Response
    {
        return Inertia::render(
            'admin/reports/arrears-aging',
            $this->aging->present($this->actor($request), $request->filters()),
        );
    }

    public function pdf(ArrearsAgingRequest $request): StreamedResponse
    {
        return $this->pdfExport->download(
            $this->aging->export($this->actor($request), $request->filters()),
        );
    }

    public function word(ArrearsAgingRequest $request): StreamedResponse
    {
        return $this->wordExport->download(
            $this->aging->export($this->actor($request), $request->filters()),
        );
    }

    public function workbook(ArrearsAgingRequest $request): BinaryFileResponse
    {
        return $this->workbookExport->download(
            $this->aging->export($this->actor($request), $request->filters()),
        );
    }
}
