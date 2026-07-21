<?php

namespace App\Modules\Users\Presenters;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;
use App\Modules\Users\Support\UserOptions;

class UserFormPresenter
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly ResourcePresenter $resources,
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
        } else {
            $this->access->ensureManager($actor);
        }

        $fields = [];

        if ($actor->hasRole('superadmin') && ! $target) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.users.portfolio'),
                'type' => 'select',
                'help' => trans('app.users.portfolio_help'),
                'options' => [
                    ['value' => '', 'label' => trans('app.users.no_portfolio')],
                    ...$this->activePortfolioOptions(),
                ],
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'name', 'label' => trans('app.users.name'), 'required' => true],
        ];

        if (! $target) {
            $fields[] = [
                'name' => 'email',
                'label' => trans('app.users.email'),
                'type' => 'email',
                'required' => true,
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'phone', 'label' => trans('app.users.phone')],
            [
                'name' => 'preferred_locale',
                'label' => trans('app.users.preferred_language'),
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'en', 'label' => trans('app.users.english')],
                    ['value' => 'ar', 'label' => trans('app.users.arabic')],
                ],
            ],
            [
                'name' => 'status',
                'label' => trans('app.users.status'),
                'type' => 'select',
                'required' => true,
                'help' => trans('app.users.status_help'),
                'options' => collect(UserOptions::STATUSES)
                    ->map(fn (string $status): array => [
                        'value' => $status,
                        'label' => trans("app.status.{$status}"),
                    ])
                    ->all(),
            ],
            [
                'name' => 'role',
                'label' => trans('app.users.role'),
                'type' => 'select',
                'required' => true,
                'help' => trans('app.users.role_help'),
                'options' => collect(UserOptions::assignableRoles($actor, $target))
                    ->map(fn (string $role): array => [
                        'value' => $role,
                        'label' => trans("app.roles.{$role}"),
                    ])
                    ->all(),
            ],
            [
                'name' => 'password',
                'label' => $target ? trans('app.users.new_temporary_password') : trans('app.users.temporary_password'),
                'type' => 'password',
                'required' => ! $target,
                'help' => $target
                    ? trans('app.users.password_edit_help')
                    : trans('app.users.password_create_help'),
            ],
        ];
        $fields = $this->resources->sectionFields($fields, [
            trans('app.users.access_rule') => [
                'description' => trans('app.users.access_rule_help'),
                'fields' => ['portfolio_id', 'role', 'status', 'preferred_locale'],
            ],
            trans('app.users.identity') => [
                'description' => trans('app.users.identity_help'),
                'fields' => ['name', 'email', 'phone'],
            ],
            trans('app.users.security') => [
                'description' => trans('app.users.security_help'),
                'fields' => ['password'],
            ],
        ]);
        $currentRole = $target?->roles->first()?->name;
        $assignableRoles = UserOptions::assignableRoles($actor, $target);
        $defaultRole = $currentRole ?: (in_array('tenant', $assignableRoles, true) ? 'tenant' : $assignableRoles[0]);

        return [
            'title' => $target ? trans('app.users.edit_user') : trans('app.users.create_user'),
            'description' => $target
                ? trans('app.users.edit_description')
                : trans('app.users.create_description'),
            'backHref' => $target ? route('users.show', $target) : route('users.index'),
            'backLabel' => $target ? trans('app.users.user_detail') : trans('app.users.all_users'),
            'action' => $target ? route('users.update', $target) : route('users.store'),
            'method' => $target ? 'put' : 'post',
            'submitLabel' => $target ? trans('app.users.update_user') : trans('app.users.create_user'),
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) ($target?->portfolio_id ?? $defaults['portfolio_id'] ?? $actor->portfolio_id ?? ''),
                'name' => $target?->name ?? '',
                'email' => $target?->email ?? '',
                'phone' => $target?->phone ?? '',
                'preferred_locale' => $target?->preferred_locale ?? 'en',
                'status' => $target?->status ?? 'active',
                'role' => $defaultRole,
                'password' => '',
            ],
        ];
    }

    /** @return array<int, array{value:int,label:string}> */
    private function activePortfolioOptions(): array
    {
        $nameColumn = app()->isLocale('ar') ? 'name_ar' : 'name_en';

        return Portfolio::query()
            ->where('status', 'active')
            ->orderBy($nameColumn)
            ->get(['id', 'name_en', 'name_ar', 'code'])
            ->map(fn (Portfolio $portfolio): array => [
                'value' => $portfolio->id,
                'label' => trim(($this->resources->localized($portfolio->name_en, $portfolio->name_ar) ?? '').' · '.$portfolio->code),
            ])
            ->all();
    }
}
