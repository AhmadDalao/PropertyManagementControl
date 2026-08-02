<?php

namespace App\Http\Controllers;

use App\Modules\Reports\Actions\RentRollPdfExport;
use App\Modules\Reports\Actions\RentRollWordExport;
use App\Modules\Reports\Actions\RentRollWorkbookExport;
use App\Modules\Reports\Queries\RentRollQuery;
use App\Modules\Reports\Requests\RentRollRequest;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RentRollController extends Controller
{
    public function __construct(
        private readonly RentRollQuery $rentRoll,
        private readonly RentRollPdfExport $pdfExport,
        private readonly RentRollWordExport $wordExport,
        private readonly RentRollWorkbookExport $workbookExport,
    ) {}

    public function index(RentRollRequest $request): Response
    {
        return Inertia::render(
            'admin/reports/rent-roll',
            $this->rentRoll->present($this->actor($request), $request->filters()),
        );
    }

    public function pdf(RentRollRequest $request): StreamedResponse
    {
        return $this->pdfExport->download(
            $this->rentRoll->export($this->actor($request), $request->filters()),
        );
    }

    public function word(RentRollRequest $request): StreamedResponse
    {
        return $this->wordExport->download(
            $this->rentRoll->export($this->actor($request), $request->filters()),
        );
    }

    public function workbook(RentRollRequest $request): BinaryFileResponse
    {
        return $this->workbookExport->download(
            $this->rentRoll->export($this->actor($request), $request->filters()),
        );
    }
}
