<?php

namespace App\Http\Controllers;

use App\Modules\SystemReadiness\Actions\ReadinessPdfExport;
use App\Modules\SystemReadiness\Actions\ReadinessWordExport;
use App\Modules\SystemReadiness\Actions\ReadinessWorkbookExport;
use App\Modules\SystemReadiness\Presenters\ReadinessReportPresenter;
use App\Modules\SystemReadiness\Requests\ReadinessIndexRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SystemReadinessReportController extends Controller
{
    public function __construct(
        private readonly ReadinessReportPresenter $report,
        private readonly ReadinessPdfExport $pdfExport,
        private readonly ReadinessWordExport $wordExport,
        private readonly ReadinessWorkbookExport $workbookExport,
    ) {}

    public function pdf(ReadinessIndexRequest $request): StreamedResponse
    {
        return $this->pdfExport->download($this->data($request));
    }

    public function word(ReadinessIndexRequest $request): StreamedResponse
    {
        return $this->wordExport->download($this->data($request));
    }

    public function workbook(ReadinessIndexRequest $request): BinaryFileResponse
    {
        return $this->workbookExport->download($this->data($request));
    }

    /** @return array<string, mixed> */
    private function data(ReadinessIndexRequest $request): array
    {
        return $this->report->present(
            $this->actor($request),
            $request->portfolioId(),
        );
    }
}
