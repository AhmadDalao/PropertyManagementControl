<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Modules\Maintenance\Actions\MaintenanceServiceReportPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MaintenanceServiceReportController extends Controller
{
    public function __invoke(
        Request $request,
        MaintenanceRequest $maintenanceRequest,
        MaintenanceServiceReportPdf $report,
    ): StreamedResponse {
        return $report->download($this->actor($request), $maintenanceRequest);
    }
}
