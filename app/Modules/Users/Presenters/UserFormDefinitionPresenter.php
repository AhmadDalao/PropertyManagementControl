<?php

namespace App\Modules\Users\Presenters;

use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Data\UserFormData;
use App\Modules\Users\Queries\UserFormOptionsQuery;
use App\Modules\Users\Support\UserOptions;

final class UserFormDefinitionPresenter
{
    public function __construct(
        private readonly UserFormOptionsQuery $options,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array{fields:array<int, array<string, mixed>>,initialValues:array<string, mixed>} */
    public function present(UserFormData $data): array
    {
        $target = $data->target;
        $fields = [];

        if ($data->actor->hasRole('superadmin') && ! $target) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.users.portfolio'),
                'type' => 'select',
                'help' => trans('app.users.portfolio_help'),
                'options' => [
                    ['value' => '', 'label' => trans('app.users.no_portfolio')],
                    ...$this->options->activePortfolios(),
                ],
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'name', 'label' => trans('app.users.name'), 'required' => true],
            [
                'name' => 'email',
                'label' => trans('app.users.email'),
                'type' => 'email',
                'required' => true,
                ...($target ? ['help' => trans('app.users.email_change_help')] : []),
            ],
            ['name' => 'phone', 'label' => trans('app.users.phone')],
            $this->localeField(),
            $this->statusField(),
            $this->roleField($data),
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
        $assignableRoles = UserOptions::assignableRoles($data->actor, $target);
        $currentRole = $target?->roles->first()?->name;
        $initialValues = $target
            ? [
                'portfolio_id' => (string) ($target->portfolio_id ?? ''),
                'name' => $target->name,
                'email' => $target->email,
                'phone' => $target->phone ?? '',
                'preferred_locale' => $target->preferred_locale,
                'status' => $target->status,
                'role' => $currentRole ?: $assignableRoles[0],
                'password' => '',
            ]
            : [
                'portfolio_id' => (string) ($data->defaults['portfolio_id'] ?? $data->actor->portfolio_id ?? ''),
                'name' => '',
                'email' => '',
                'phone' => '',
                'preferred_locale' => 'en',
                'status' => 'active',
                'role' => in_array('tenant', $assignableRoles, true) ? 'tenant' : $assignableRoles[0],
                'password' => '',
            ];

        return [
            'fields' => $fields,
            'initialValues' => $initialValues,
        ];
    }

    /** @return array<string, mixed> */
    private function localeField(): array
    {
        return [
            'name' => 'preferred_locale',
            'label' => trans('app.users.preferred_language'),
            'type' => 'select',
            'required' => true,
            'options' => [
                ['value' => 'en', 'label' => trans('app.users.english')],
                ['value' => 'ar', 'label' => trans('app.users.arabic')],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function statusField(): array
    {
        return [
            'name' => 'status',
            'label' => trans('app.users.status'),
            'type' => 'select',
            'required' => true,
            'help' => trans('app.users.status_help'),
            'options' => collect(UserOptions::STATUSES)->map(fn (string $status): array => [
                'value' => $status,
                'label' => trans("app.status.{$status}"),
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function roleField(UserFormData $data): array
    {
        return [
            'name' => 'role',
            'label' => trans('app.users.role'),
            'type' => 'select',
            'required' => true,
            'help' => trans('app.users.role_help'),
            'options' => collect(UserOptions::assignableRoles($data->actor, $data->target))
                ->map(fn (string $role): array => [
                    'value' => $role,
                    'label' => trans("app.roles.{$role}"),
                ])->all(),
        ];
    }
}
