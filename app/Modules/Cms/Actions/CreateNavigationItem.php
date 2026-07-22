<?php

namespace App\Modules\Cms\Actions;

use App\Models\NavigationItem;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsInputGuard;
use App\Modules\Cms\Support\NavigationAttributes;
use App\Modules\Cms\Support\NavigationDestination;
use App\Modules\Cms\Support\NavigationParentPolicy;
use Illuminate\Support\Facades\DB;

final class CreateNavigationItem
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsInputGuard $input,
        private readonly NavigationParentPolicy $parents,
        private readonly NavigationDestination $destination,
        private readonly NavigationAttributes $attributes,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): NavigationItem
    {
        $this->access->ensureAdmin($actor);
        $data = $this->input->navigation($data);

        return DB::transaction(function () use ($data): NavigationItem {
            $this->parents->ensure($data);
            $data = $this->destination->resolve($data);

            return NavigationItem::query()->create($this->attributes->forCreate($data));
        }, 3);
    }
}
