<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Leases\Queries\LeaseDetailQuery;
use App\Modules\Leases\Support\LeaseOptions;
use App\Modules\Shared\ResourcePresenter;

final class LeaseDetailPresenter
{
    public function __construct(
        private readonly LeaseDetailQuery $query,
        private readonly LeaseDetailHeaderPresenter $header,
        private readonly LeaseDetailOverviewPresenter $overview,
        private readonly LeaseRelatedPresenter $related,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(Lease $target, User $actor): array
    {
        $data = $this->query->get($target, $actor);
        $documents = $data->adminMode
            ? $data->lease->documents
            : $data->lease->documents
                ->where('is_public', true)
                ->whereIn('type', LeaseOptions::TENANT_DOCUMENT_TYPES);

        return [
            'header' => $this->header->present($data),
            'stats' => $this->overview->stats($data),
            'sections' => $this->overview->sections($data),
            'related' => $this->related->present($data),
            'documents' => $this->resources->documentStrip($documents),
            'timeline' => $data->adminMode ? $this->resources->activityTimeline($data->lease) : [],
        ];
    }
}
