<?php

namespace App\Modules\Cms\Actions;

use App\Models\NavigationItem;
use App\Models\User;

final class ManageNavigationItems
{
    public function __construct(
        private readonly CreateNavigationItem $create,
        private readonly UpdateNavigationItem $update,
        private readonly DeleteNavigationItem $delete,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): NavigationItem
    {
        return $this->create->handle($actor, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, NavigationItem $target, array $data): NavigationItem
    {
        return $this->update->handle($actor, $target, $data);
    }

    public function delete(User $actor, NavigationItem $target): void
    {
        $this->delete->handle($actor, $target);
    }
}
