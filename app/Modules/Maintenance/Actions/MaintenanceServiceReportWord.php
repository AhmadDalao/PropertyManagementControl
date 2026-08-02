<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Exports\Support\SimpleDocx;
use App\Modules\Maintenance\Presenters\MaintenanceServiceReportWordPresenter;
use App\Modules\Maintenance\Queries\MaintenanceDetailQuery;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class MaintenanceServiceReportWord
{
    public function __construct(
        private MaintenanceAccess $access,
        private MaintenanceDetailQuery $details,
        private MaintenanceServiceReportWordPresenter $presenter,
        private SimpleDocx $documents,
    ) {}

    public function download(User $actor, MaintenanceRequest $request): StreamedResponse
    {
        $this->access->ensureManager($actor);
        $data = $this->details->get($request, $actor);

        abort_unless(
            $data->request->status === 'resolved',
            409,
            trans('app.errors.maintenance_report_requires_resolution'),
        );

        $path = $this->documents->create($this->presenter->present($data));
        $content = (string) file_get_contents($path);
        @unlink($path);

        return response()->streamDownload(
            static fn () => print ($content),
            "maintenance-service-report-{$request->id}.docx",
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        );
    }
}
