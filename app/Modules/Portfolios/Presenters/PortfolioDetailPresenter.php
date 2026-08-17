<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Queries\PortfolioDetailQuery;
use App\Modules\Shared\ResourcePresenter;

class PortfolioDetailPresenter
{
    public function __construct(
        private readonly PortfolioDetailQuery $details,
        private readonly PortfolioOverviewPresenter $overview,
        private readonly PortfolioDetailTabPresenter $tabs,
        private readonly PortfolioWorkflowPresenter $workflow,
        private readonly PortfolioSetupProgressPresenter $setup,
        private readonly PortfolioRelatedPresenter $related,
        private readonly PortfolioModulePresenter $modules,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(Portfolio $portfolio, User $actor): array
    {
        $data = $this->details->get($portfolio, $actor);
        $progress = $this->setup->present($data, $actor);

        return [
            ...$this->overview->present($data, $actor),
            'availableTabs' => $this->tabs->present($data, $actor),
            'workflow' => $this->workflow->present($data, $actor, $progress),
            'progress' => $progress,
            'related' => $this->related->present($data, $actor),
            'modules' => $this->modules->present($data),
            'documents' => $this->resources->documentStrip($data->documents),
            'timeline' => $this->resources->activityTimeline($data->portfolio),
        ];
    }
}
