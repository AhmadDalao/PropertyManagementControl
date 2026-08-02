<?php

namespace App\Http\Controllers;

use App\Modules\ActionCenter\Actions\ActionCenterPdfExport;
use App\Modules\ActionCenter\Actions\ActionCenterWordExport;
use App\Modules\ActionCenter\Queries\ActionCenterReportQuery;
use App\Modules\ActionCenter\Requests\ActionCenterIndexRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ActionCenterReportController extends Controller
{
    public function __construct(
        private readonly ActionCenterReportQuery $report,
        private readonly ActionCenterPdfExport $pdfExport,
        private readonly ActionCenterWordExport $wordExport,
    ) {}

    public function pdf(ActionCenterIndexRequest $request): StreamedResponse
    {
        return $this->pdfExport->download(
            $this->report->handle($this->actor($request), $request->filters()),
        );
    }

    public function word(ActionCenterIndexRequest $request): StreamedResponse
    {
        return $this->wordExport->download(
            $this->report->handle($this->actor($request), $request->filters()),
        );
    }
}
