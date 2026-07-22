<?php

namespace App\Modules\Tenants\Presenters;

use App\Models\User;
use App\Modules\Tenants\Data\TenantFormData;

final class TenantCreateFormPresenter
{
    public function __construct(private readonly TenantFormDefinitionPresenter $definition) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, array $defaults): array
    {
        return [
            'title' => trans('app.tenants.create_tenant'),
            'description' => trans('app.tenants.create_description'),
            'backHref' => route('tenants.index'),
            'backLabel' => trans('app.tenants.all_tenants'),
            'action' => route('tenants.store'),
            'method' => 'post',
            'submitLabel' => trans('app.tenants.create_tenant'),
            ...$this->definition->present(new TenantFormData($actor, defaults: $defaults)),
        ];
    }
}
