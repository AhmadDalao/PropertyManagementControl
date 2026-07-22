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
        private readonly PortfolioRelatedPresenter $related,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(Portfolio $portfolio, User $actor): array
    {
        $data = $this->details->get($portfolio, $actor);

        return [
            ...$this->overview->present($data, $actor),
            'related' => $this->related->present($data, $actor),
            'documents' => $this->resources->documentStrip($data->documents),
            'timeline' => $this->resources->activityTimeline($data->portfolio),
        ];
    }
}
