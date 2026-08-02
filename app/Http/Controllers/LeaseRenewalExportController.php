<?php

namespace App\Http\Controllers;

use App\Modules\LeaseRenewals\Actions\LeaseRenewalPdfExport;
use App\Modules\LeaseRenewals\Actions\LeaseRenewalWordExport;
use App\Modules\LeaseRenewals\Queries\LeaseRenewalReportQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LeaseRenewalExportController extends Controller
{
    public function __construct(
        private readonly LeaseRenewalReportQuery $report,
        private readonly LeaseRenewalPdfExport $pdfExport,
        private readonly LeaseRenewalWordExport $wordExport,
    ) {}

    public function pdf(Request $request): StreamedResponse
    {
        return $this->pdfExport->download(
            $this->report->handle($request, $this->actor($request)),
        );
    }

    public function word(Request $request): StreamedResponse
    {
        return $this->wordExport->download(
            $this->report->handle($request, $this->actor($request)),
        );
    }
}
