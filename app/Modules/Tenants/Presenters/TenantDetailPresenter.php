<?php

namespace App\Modules\Tenants\Presenters;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Tenants\Queries\TenantDetailQuery;

final class TenantDetailPresenter
{
    public function __construct(
        private readonly TenantDetailQuery $details,
        private readonly TenantDetailHeaderPresenter $header,
        private readonly TenantDetailOverviewPresenter $overview,
        private readonly TenantRelatedPresenter $related,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(TenantProfile $tenant, User $actor): array
    {
        $data = $this->details->get($tenant, $actor);

        return [
            'header' => $this->header->present($data),
            ...$this->overview->present($data),
            'related' => $this->related->present($data),
            'documents' => $data->activeLease
                ? $this->resources->documentStrip($data->activeLease->documents)
                : [],
            'timeline' => $this->resources->activityTimeline($data->tenant),
        ];
    }
}
