<?php

namespace App\Modules\Tenants\Presenters;

use App\Modules\Shared\ResourcePresenter;
use App\Modules\Tenants\Data\TenantFormData;
use App\Modules\Tenants\Queries\TenantFormOptionsQuery;
use App\Modules\Tenants\Support\TenantOptions;

final class TenantFormFieldsPresenter
{
    public function __construct(
        private readonly TenantFormOptionsQuery $options,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function present(TenantFormData $data): array
    {
        $tenant = $data->tenant;
        $fields = [];

        if ($data->actor->hasRole('superadmin') && ! $tenant) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.tenants.portfolio'),
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => '', 'label' => trans('app.tenants.choose_portfolio')],
                    ...$this->options->activePortfolios($data->actor),
                ],
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'name', 'label' => trans('app.tenants.name'), 'required' => true],
            [
                'name' => 'email',
                'label' => trans('app.tenants.login_email'),
                'type' => 'email',
                'required' => true,
                ...($tenant ? ['help' => trans('app.tenants.email_change_help')] : []),
            ],
            [
                'name' => 'password',
                'label' => $tenant
                    ? trans('app.tenants.new_temporary_password')
                    : trans('app.tenants.temporary_password'),
                'type' => 'password',
                'required' => ! $tenant || ! $tenant->user,
                'help' => $tenant
                    ? trans('app.tenants.password_edit_help')
                    : trans('app.tenants.password_create_help'),
            ],
            ['name' => 'phone', 'label' => trans('app.tenants.phone')],
            $this->localeField(),
            $this->statusField(),
            $this->profileTypeField(),
            ['name' => 'national_id', 'label' => trans('app.tenants.national_id')],
            ['name' => 'company_name', 'label' => trans('app.tenants.company_name')],
            ['name' => 'address', 'label' => trans('app.tenants.address'), 'type' => 'textarea', 'rows' => 2],
            ['name' => 'emergency_contact_name', 'label' => trans('app.tenants.emergency_contact_name')],
            ['name' => 'emergency_contact_phone', 'label' => trans('app.tenants.emergency_contact_phone')],
            ['name' => 'notes', 'label' => trans('app.tenants.notes'), 'type' => 'textarea'],
        ];

        return $this->resources->sectionFields($fields, $this->sections());
    }

    /** @return array<string, mixed> */
    private function localeField(): array
    {
        return [
            'name' => 'preferred_locale',
            'label' => trans('app.tenants.portal_language'),
            'type' => 'select',
            'required' => true,
            'options' => [
                ['value' => 'en', 'label' => trans('app.tenants.english')],
                ['value' => 'ar', 'label' => trans('app.tenants.arabic')],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function statusField(): array
    {
        return [
            'name' => 'status',
            'label' => trans('app.tenants.status'),
            'type' => 'select',
            'required' => true,
            'options' => collect(TenantOptions::STATUSES)->map(fn (string $status): array => [
                'value' => $status,
                'label' => trans("app.status.{$status}"),
            ])->all(),
            'help' => trans('app.tenants.status_help'),
        ];
    }

    /** @return array<string, mixed> */
    private function profileTypeField(): array
    {
        return [
            'name' => 'profile_type',
            'label' => trans('app.tenants.profile_type'),
            'type' => 'select',
            'required' => true,
            'options' => collect(TenantOptions::PROFILE_TYPES)->map(fn (string $type): array => [
                'value' => $type,
                'label' => trans("app.tenants.{$type}"),
            ])->all(),
        ];
    }

    /** @return array<string, array{description:string,fields:array<int, string>}> */
    private function sections(): array
    {
        return [
            trans('app.tenants.portal_account') => [
                'description' => trans('app.tenants.portal_account_help'),
                'fields' => ['portfolio_id', 'name', 'email', 'password', 'phone', 'preferred_locale', 'status'],
            ],
            trans('app.tenants.identity') => [
                'description' => trans('app.tenants.identity_help'),
                'fields' => ['profile_type', 'national_id', 'company_name', 'address'],
            ],
            trans('app.tenants.emergency_and_notes') => [
                'description' => trans('app.tenants.emergency_and_notes_help'),
                'fields' => ['emergency_contact_name', 'emergency_contact_phone', 'notes'],
            ],
        ];
    }
}
