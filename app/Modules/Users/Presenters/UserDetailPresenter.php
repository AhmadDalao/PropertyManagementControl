<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Queries\UserDetailQuery;

final class UserDetailPresenter
{
    public function __construct(
        private readonly UserDetailQuery $details,
        private readonly UserDetailHeaderPresenter $header,
        private readonly UserDetailOverviewPresenter $overview,
        private readonly UserRelatedPresenter $related,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $target, User $actor): array
    {
        $data = $this->details->get($target, $actor);
        $overview = $this->overview->present($data->user);

        return [
            'header' => $this->header->present($data->user),
            ...$overview,
            'related' => $this->related->present($data->stakeholders, $data->maintenance),
            'documents' => $this->resources->documentStrip($data->documents),
            'timeline' => $this->resources->activityTimeline($data->user),
        ];
    }
}
