<?php

namespace App\Modules\Leases\Presenters;

use App\Models\CollectionFollowUp;
use App\Modules\Leases\Data\LeaseDetailData;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;

final class LeaseTimelinePresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function present(LeaseDetailData $data): array
    {
        $items = $this->resources->activityTimeline($data->lease);

        if ($data->lease->moveOut) {
            $items = [
                ...$items,
                ...$this->resources->activityTimeline($data->lease->moveOut),
            ];
        }

        $followUpIds = PortfolioModules::enabledForUser($data->actor, 'payments')
            ? $data->lease->collectionFollowUps->pluck('id')->all()
            : [];
        if ($followUpIds !== []) {
            $items = [
                ...$items,
                ...$this->resources->activityTimelineFor(
                    new CollectionFollowUp,
                    $followUpIds,
                ),
            ];
        }

        return collect($items)
            ->sortByDesc('created_at')
            ->take(8)
            ->values()
            ->all();
    }
}
