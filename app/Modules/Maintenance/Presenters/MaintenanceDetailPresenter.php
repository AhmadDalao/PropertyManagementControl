<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Queries\MaintenanceDetailQuery;
use App\Modules\Shared\ResourcePresenter;

class MaintenanceDetailPresenter
{
    public function __construct(
        private readonly MaintenanceDetailQuery $details,
        private readonly MaintenanceDetailOverviewPresenter $overview,
        private readonly MaintenanceRelatedPresenter $related,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(MaintenanceRequest $request, User $actor): array
    {
        $data = $this->details->get($request, $actor);

        return [
            ...$this->overview->present($data),
            'related' => $this->related->present($data),
            'documents' => [],
            'timeline' => $data->tenantMode
                ? []
                : $this->resources->activityTimeline($data->request),
        ];
    }
}
