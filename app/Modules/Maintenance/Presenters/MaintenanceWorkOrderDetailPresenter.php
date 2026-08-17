<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Modules\Maintenance\Queries\MaintenanceWorkOrderDetailQuery;
use App\Modules\Shared\ResourcePresenter;

final class MaintenanceWorkOrderDetailPresenter
{
    public function __construct(
        private readonly MaintenanceWorkOrderDetailQuery $query,
        private readonly MaintenanceWorkOrderHeaderPresenter $header,
        private readonly MaintenanceWorkOrderWorkflowPresenter $workflow,
        private readonly MaintenanceWorkOrderOverviewPresenter $overview,
        private readonly MaintenanceWorkOrderNoticesPresenter $notices,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(MaintenanceWorkOrder $workOrder, User $actor): array
    {
        $data = $this->query->get($workOrder, $actor);

        return [
            'header' => $this->header->present($data),
            'availableTabs' => ['overview', 'assignment', 'schedule', 'cost', 'completion', 'history'],
            'workflow' => $this->workflow->present($data),
            'stats' => $this->overview->stats($data),
            'sections' => $this->overview->sections($data),
            'notices' => $this->notices->present($data),
            'timeline' => $this->resources->activityTimeline($workOrder),
        ];
    }
}
