<?php

namespace App\Modules\Cms\Presenters;

use App\Models\CmsPage;
use App\Models\User;
use App\Modules\Cms\Queries\CmsBuilderQuery;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Shared\ResourcePresenter;

class CmsBuilderPresenter
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsBuilderQuery $builder,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, CmsPage $page): array
    {
        $this->access->ensureAdmin($actor);

        return [
            ...$this->builder->handle($page),
            'timeline' => $this->resources->activityTimeline($page),
        ];
    }
}
