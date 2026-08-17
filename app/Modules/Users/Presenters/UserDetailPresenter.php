<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Queries\UserDetailQuery;

final class UserDetailPresenter
{
    public function __construct(
        private readonly UserDetailQuery $details,
        private readonly UserDetailHeaderPresenter $header,
        private readonly UserDetailOverviewPresenter $overview,
        private readonly UserDetailTabPresenter $tabs,
        private readonly UserWorkflowPresenter $workflow,
        private readonly UserRelatedPresenter $related,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $target, User $actor): array
    {
        $data = $this->details->get($target, $actor);

        return [
            'header' => $this->header->present($data->user, $actor),
            'availableTabs' => $this->tabs->present($actor),
            'workflow' => $this->workflow->present($data, $actor),
            ...$this->overview->present($data->user, $actor),
            'related' => $this->related->present($data->stakeholders, $data->maintenance, $actor),
            'documents' => PortfolioModules::enabledForUser($actor, 'documents')
                ? $this->resources->documentStrip($data->documents)
                : [],
            'timeline' => $this->resources->activityTimeline($data->user),
        ];
    }
}
