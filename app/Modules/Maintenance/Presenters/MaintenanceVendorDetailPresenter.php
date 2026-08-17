<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceVendor;
use App\Models\User;
use App\Modules\Maintenance\Queries\MaintenanceVendorDetailQuery;
use App\Modules\Shared\ResourcePresenter;

final class MaintenanceVendorDetailPresenter
{
    public function __construct(
        private readonly MaintenanceVendorDetailQuery $query,
        private readonly MaintenanceVendorHeaderPresenter $header,
        private readonly MaintenanceVendorOverviewPresenter $overview,
        private readonly MaintenanceVendorWorkloadPresenter $workload,
        private readonly MaintenanceVendorWorkflowPresenter $workflow,
        private readonly MaintenanceVendorNoticesPresenter $notices,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(MaintenanceVendor $vendor, User $actor): array
    {
        $data = $this->query->get($vendor, $actor);

        return [
            'header' => $this->header->present($data),
            'availableTabs' => ['overview', 'workload', 'schedule', 'financial', 'history'],
            'workflow' => $this->workflow->present($data),
            'stats' => $this->overview->stats($data),
            'sections' => $this->overview->sections($data),
            'workload' => $this->workload->present($data),
            'notices' => $this->notices->present($data),
            'timeline' => $this->resources->activityTimeline($data->vendor),
        ];
    }
}
