<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Lease;
use App\Models\User;
use App\Modules\LeaseMoveOuts\Presenters\LeaseMoveOutProgressPresenter;
use App\Modules\Leases\Queries\LeaseDetailQuery;
use App\Modules\Leases\Support\LeaseOptions;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;

final class LeaseDetailPresenter
{
    public function __construct(
        private readonly LeaseDetailQuery $query,
        private readonly LeaseDetailHeaderPresenter $header,
        private readonly LeaseWorkflowPresenter $workflow,
        private readonly LeaseMoveInProgressPresenter $moveInProgress,
        private readonly LeaseMoveOutProgressPresenter $moveOutProgress,
        private readonly LeaseDetailOverviewPresenter $overview,
        private readonly LeaseRelatedPresenter $related,
        private readonly LeaseTimelinePresenter $timeline,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(Lease $target, User $actor): array
    {
        $data = $this->query->get($target, $actor);
        $documents = collect();

        if (PortfolioModules::enabledForUser($actor, 'documents')) {
            $documents = $data->adminMode
                ? $data->lease->documents
                : $data->lease->documents
                    ->where('is_public', true)
                    ->whereIn('type', LeaseOptions::TENANT_DOCUMENT_TYPES);
        }

        return [
            'mode' => $data->adminMode ? 'admin' : 'tenant',
            'header' => $this->header->present($data),
            'workflow' => $this->workflow->present($data),
            'progress' => $this->moveOutProgress->present($data)
                ?? $this->moveInProgress->present($data),
            'stats' => $this->overview->stats($data),
            'sections' => $this->overview->sections($data),
            'related' => $this->related->present($data),
            'documents' => $this->resources->documentStrip($documents),
            'timeline' => $data->adminMode ? $this->timeline->present($data) : [],
        ];
    }
}
