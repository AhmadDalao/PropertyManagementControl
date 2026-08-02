<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Modules\Reports\Actions\PropertyOperatingReportPdfExport;
use App\Modules\Reports\Actions\PropertyOperatingReportWordExport;
use App\Modules\Reports\Actions\PropertyOperatingReportWorkbookExport;
use App\Modules\Reports\Presenters\PropertyReportPresenter;
use App\Modules\Reports\Requests\PropertyReportRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PropertyReportExportController extends Controller
{
    public function __construct(
        private readonly PropertyReportPresenter $reports,
        private readonly PropertyOperatingReportPdfExport $pdfExport,
        private readonly PropertyOperatingReportWordExport $wordExport,
        private readonly PropertyOperatingReportWorkbookExport $workbookExport,
    ) {}

    public function pdf(PropertyReportRequest $request, Asset $asset): StreamedResponse
    {
        return $this->pdfExport->download($this->data($request, $asset));
    }

    public function word(PropertyReportRequest $request, Asset $asset): StreamedResponse
    {
        return $this->wordExport->download($this->data($request, $asset));
    }

    public function workbook(PropertyReportRequest $request, Asset $asset): BinaryFileResponse
    {
        return $this->workbookExport->download($this->data($request, $asset));
    }

    /** @return array<string, mixed> */
    private function data(PropertyReportRequest $request, Asset $asset): array
    {
        return $this->reports->present(
            $this->actor($request),
            $asset,
            $request->filters($asset),
            true,
        );
    }
}
