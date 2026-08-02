<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Modules\Maintenance\Actions\MaintenanceServiceReportWord;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MaintenanceServiceReportWordController extends Controller
{
    public function __invoke(
        Request $request,
        MaintenanceRequest $maintenanceRequest,
        MaintenanceServiceReportWord $report,
    ): StreamedResponse {
        return $report->download($this->actor($request), $maintenanceRequest);
    }
}
