<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;
use App\Modules\Users\Data\UserFormData;

final class UserEditFormPresenter
{
    public function __construct(private readonly UserFormDefinitionPresenter $definition) {}

    /** @return array<string, mixed> */
    public function present(User $actor, User $target): array
    {
        $definition = $this->definition->present(new UserFormData($actor, $target));

        return [
            'title' => trans('app.users.edit_user'),
            'description' => trans('app.users.edit_description'),
            'backHref' => route('users.show', $target),
            'backLabel' => trans('app.users.user_detail'),
            'action' => route('users.update', $target),
            'method' => 'put',
            'submitLabel' => trans('app.users.update_user'),
            ...$definition,
        ];
    }
}
