<?php

namespace App\Modules\Tenants\Presenters;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Tenants\Support\TenantAccess;
use App\Modules\Tenants\Support\TenantOptions;

class TenantFormPresenter
{
    public function __construct(
        private readonly TenantAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly ResourcePresenter $resources,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?TenantProfile $tenant = null, array $defaults = []): array
    {
        if ($tenant) {
            $this->access->ensureCanManage($actor, $tenant);
            $tenant->loadMissing('user');
        } else {
            $this->access->ensureManager($actor);
        }

        $fields = [];

        if ($actor->hasRole('superadmin') && ! $tenant) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.tenants.portfolio'),
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => '', 'label' => trans('app.tenants.choose_portfolio')],
                    ...collect($this->portfolios->options($actor))
                        ->map(fn (array $portfolio): array => [
                            'value' => $portfolio['id'],
                            'label' => $portfolio['name'],
                        ])
                        ->all(),
                ],
            ];
        }

        $passwordRequired = ! $tenant || ! $tenant->user;
        $fields = [
            ...$fields,
            ['name' => 'name', 'label' => trans('app.tenants.name'), 'required' => true],
            ['name' => 'email', 'label' => trans('app.tenants.login_email'), 'type' => 'email', 'required' => true],
            [
                'name' => 'password',
                'label' => $tenant ? trans('app.tenants.new_temporary_password') : trans('app.tenants.temporary_password'),
                'type' => 'password',
                'required' => $passwordRequired,
                'help' => $tenant
                    ? trans('app.tenants.password_edit_help')
                    : trans('app.tenants.password_create_help'),
            ],
            ['name' => 'phone', 'label' => trans('app.tenants.phone')],
            [
                'name' => 'preferred_locale',
                'label' => trans('app.tenants.portal_language'),
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'en', 'label' => trans('app.tenants.english')],
                    ['value' => 'ar', 'label' => trans('app.tenants.arabic')],
                ],
            ],
            [
                'name' => 'status',
                'label' => trans('app.tenants.status'),
                'type' => 'select',
                'required' => true,
                'options' => $this->statusOptions(),
                'help' => trans('app.tenants.status_help'),
            ],
            [
                'name' => 'profile_type',
                'label' => trans('app.tenants.profile_type'),
                'type' => 'select',
                'required' => true,
                'options' => collect(TenantOptions::PROFILE_TYPES)
                    ->map(fn (string $type): array => [
                        'value' => $type,
                        'label' => trans("app.tenants.{$type}"),
                    ])
                    ->all(),
            ],
            ['name' => 'national_id', 'label' => trans('app.tenants.national_id')],
            ['name' => 'company_name', 'label' => trans('app.tenants.company_name')],
            ['name' => 'address', 'label' => trans('app.tenants.address'), 'type' => 'textarea', 'rows' => 2],
            ['name' => 'emergency_contact_name', 'label' => trans('app.tenants.emergency_contact_name')],
            ['name' => 'emergency_contact_phone', 'label' => trans('app.tenants.emergency_contact_phone')],
            ['name' => 'notes', 'label' => trans('app.tenants.notes'), 'type' => 'textarea'],
        ];
        $fields = $this->resources->sectionFields($fields, [
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
        ]);
        $tenantUser = $tenant ? $tenant->user : null;

        return [
            'title' => $tenant ? trans('app.tenants.edit_tenant') : trans('app.tenants.create_tenant'),
            'description' => $tenant
                ? trans('app.tenants.edit_description')
                : trans('app.tenants.create_description'),
            'backHref' => $tenant ? route('tenants.show', $tenant) : route('tenants.index'),
            'backLabel' => $tenant ? trans('app.tenants.tenant_detail') : trans('app.tenants.all_tenants'),
            'action' => $tenant ? route('tenants.update', $tenant) : route('tenants.store'),
            'method' => $tenant ? 'put' : 'post',
            'submitLabel' => $tenant ? trans('app.tenants.update_tenant') : trans('app.tenants.create_tenant'),
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) ($tenant ? $tenant->portfolio_id : ($defaults['portfolio_id'] ?? $actor->portfolio_id ?? '')),
                'name' => $tenantUser ? $tenantUser->name : '',
                'email' => $tenantUser ? $tenantUser->email : '',
                'password' => '',
                'phone' => $tenantUser ? ($tenantUser->phone ?? '') : '',
                'preferred_locale' => $tenantUser ? $tenantUser->preferred_locale : 'en',
                'status' => $tenant ? $tenant->status : 'active',
                'profile_type' => $tenant ? $tenant->profile_type : 'individual',
                'national_id' => $tenant ? ($tenant->national_id ?? '') : '',
                'company_name' => $tenant ? ($tenant->company_name ?? '') : '',
                'address' => $tenant ? ($tenant->address ?? '') : '',
                'emergency_contact_name' => $tenant ? ($tenant->emergency_contact_name ?? '') : '',
                'emergency_contact_phone' => $tenant ? ($tenant->emergency_contact_phone ?? '') : '',
                'notes' => $tenant ? ($tenant->notes ?? '') : '',
            ],
        ];
    }

    /** @return array<int, array{value:string,label:string}> */
    private function statusOptions(): array
    {
        return collect(TenantOptions::STATUSES)
            ->map(fn (string $status): array => [
                'value' => $status,
                'label' => trans("app.status.{$status}"),
            ])
            ->all();
    }
}
