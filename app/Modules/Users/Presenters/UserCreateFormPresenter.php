<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;
use App\Modules\Users\Data\UserFormData;

final class UserCreateFormPresenter
{
    public function __construct(private readonly UserFormDefinitionPresenter $definition) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, array $defaults): array
    {
        $definition = $this->definition->present(new UserFormData($actor, defaults: $defaults));

        return [
            'title' => trans('app.users.create_user'),
            'description' => trans('app.users.create_description'),
            'backHref' => route('users.index'),
            'backLabel' => trans('app.users.all_users'),
            'action' => route('users.store'),
            'method' => 'post',
            'submitLabel' => trans('app.users.create_user'),
            ...$definition,
        ];
    }
}
