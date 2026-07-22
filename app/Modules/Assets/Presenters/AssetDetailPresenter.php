<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Queries\AssetDetailQuery;
use App\Modules\Shared\ResourcePresenter;

class AssetDetailPresenter
{
    public function __construct(
        private readonly AssetDetailQuery $details,
        private readonly AssetDetailOverviewPresenter $overview,
        private readonly AssetDecisionCardsPresenter $decisions,
        private readonly AssetRelatedPresenter $related,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(Asset $asset, User $actor): array
    {
        $data = $this->details->get($asset, $actor);

        return [
            ...$this->overview->present($data),
            'decisionCards' => $this->decisions->present($data),
            'related' => $this->related->present($data),
            'documents' => $this->resources->documentStrip($data->documents),
            'timeline' => $this->resources->activityTimeline($data->asset),
        ];
    }
}
