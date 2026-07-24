<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Queries\AssetDetailQuery;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;

class AssetDetailPresenter
{
    public function __construct(
        private readonly AssetDetailQuery $details,
        private readonly AssetDetailOverviewPresenter $overview,
        private readonly AssetWorkflowPresenter $workflow,
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
            'workflow' => $this->workflow->present($data),
            'decisionCards' => $this->decisions->present($data),
            'related' => $this->related->present($data),
            'documents' => PortfolioModules::enabledForUser($actor, 'documents')
                ? $this->resources->documentStrip($data->operations->documents)
                : [],
            'timeline' => $this->resources->activityTimelineFor($data->asset, $data->operations->assetIds),
        ];
    }
}
