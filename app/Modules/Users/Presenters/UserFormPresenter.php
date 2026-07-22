<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;
use App\Modules\Users\Support\UserAccess;

final class UserFormPresenter
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly UserCreateFormPresenter $create,
        private readonly UserEditFormPresenter $edit,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?User $target = null, array $defaults = []): array
    {
        if ($target) {
            $this->access->ensureCanManage($actor, $target);
            $target->loadMissing('roles');

            return $this->edit->present($actor, $target);
        }

        $this->access->ensureManager($actor);

        return $this->create->present($actor, $defaults);
    }
}
