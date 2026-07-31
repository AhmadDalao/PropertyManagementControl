<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Documents\Support\BilingualPdf;
use App\Modules\Maintenance\Queries\MaintenanceDetailQuery;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class MaintenanceServiceReportPdf
{
    public function __construct(
        private MaintenanceDetailQuery $details,
        private BilingualPdf $pdf,
    ) {}

    public function download(
        User $actor,
        MaintenanceRequest $request,
    ): StreamedResponse {
        $data = $this->details->get($request, $actor);

        abort_unless(
            $data->request->status === 'resolved',
            409,
            trans('app.errors.maintenance_report_requires_resolution'),
        );

        $content = $this->pdf
            ->loadView('pdf.maintenance-service-report', ['data' => $data])
            ->setPaper('a4')
            ->output();

        return response()->streamDownload(
            static fn () => print ($content),
            "maintenance-service-report-{$request->id}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }
}
