<?php

namespace App\Modules\Tenants\Presenters;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Tenants\Data\TenantFormData;

final class TenantEditFormPresenter
{
    public function __construct(private readonly TenantFormDefinitionPresenter $definition) {}

    /** @return array<string, mixed> */
    public function present(User $actor, TenantProfile $tenant): array
    {
        return [
            'title' => trans('app.tenants.edit_tenant'),
            'description' => trans('app.tenants.edit_description'),
            'backHref' => route('tenants.show', $tenant),
            'backLabel' => trans('app.tenants.tenant_detail'),
            'action' => route('tenants.update', $tenant),
            'method' => 'put',
            'submitLabel' => trans('app.tenants.update_tenant'),
            ...$this->definition->present(new TenantFormData($actor, $tenant)),
        ];
    }
}
