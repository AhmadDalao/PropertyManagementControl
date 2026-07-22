<?php

namespace App\Modules\Users\Presenters;

use App\Models\User;

final class UserDetailHeaderPresenter
{
    /** @return array<string, mixed> */
    public function present(User $user): array
    {
        $actions = [[
            'label' => trans('app.users.edit_user'),
            'href' => route('users.edit', $user),
            'variant' => 'primary',
        ]];

        if ($user->tenantProfile) {
            $actions[] = [
                'label' => trans('app.users.open_tenant_profile'),
                'href' => route('tenants.show', $user->tenantProfile),
                'variant' => 'secondary',
            ];
        }

        if ($user->status !== 'suspended') {
            $actions[] = [
                'label' => trans('app.users.suspend_user'),
                'href' => route('users.destroy', $user),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.users.archive_confirm', ['name' => $user->name]),
            ];
        }

        return [
            'eyebrow' => trans('app.users.detail_eyebrow'),
            'title' => $user->name,
            'description' => trans('app.users.detail_description', [
                'role' => $this->roles($user),
                'status' => trans("app.status.{$user->status}"),
            ]),
            'backHref' => route('users.index'),
            'backLabel' => trans('app.users.all_users'),
            'actions' => $actions,
        ];
    }

    private function roles(User $user): string
    {
        return $user->roles->pluck('name')
            ->map(fn (string $role): string => trans("app.roles.{$role}"))
            ->join(' / ');
    }
}
