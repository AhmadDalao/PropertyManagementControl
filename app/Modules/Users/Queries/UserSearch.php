<?php

namespace App\Modules\Users\Queries;

use App\Models\User;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;
use App\Modules\Shared\TableQuery;
use App\Modules\Users\Support\UserAccess;

class UserSearch extends ModuleSearchSource
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly TableQuery $tables,
        private readonly SearchResultPresenter $results,
    ) {}

    public function results(User $actor, string $query): array
    {
        if (! $this->isManager($actor) || ! $this->moduleEnabled($actor, 'users')) {
            return [];
        }

        $users = $this->access
            ->directoryScope(User::query(), $actor)
            ->with('roles');
        $this->tables->search($users, $query, ['name', 'email', 'phone']);

        return $users
            ->limit(5)
            ->get()
            ->map(fn (User $user): array => $this->results->result(
                trans('app.nav.users'),
                $user->name,
                $user->email,
                $user->roles
                    ->pluck('name')
                    ->map(fn (string $role): string => trans("app.roles.{$role}"))
                    ->join(', '),
                $this->access->recordHref($actor, $user) ?? route('users.index'),
            ))
            ->all();
    }
}
