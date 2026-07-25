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
        $form = $this->definition->present(new TenantFormData($actor, defaults: $defaults));
        $continueToLease = ($defaults['next'] ?? null) === 'lease';

        if ($continueToLease) {
            array_unshift($form['fields'], [
                'name' => 'next',
                'label' => trans('app.tenants.continue_to_lease'),
                'type' => 'hidden',
                'section' => trans('app.tenants.portal_account'),
                'sectionDescription' => trans('app.tenants.portal_account_help'),
            ]);
            $form['initialValues']['next'] = 'lease';
        }

        return [
            'title' => trans($continueToLease
                ? 'app.tenants.onboard_tenant'
                : 'app.tenants.create_tenant'),
            'description' => trans($continueToLease
                ? 'app.tenants.onboarding_description'
                : 'app.tenants.create_description'),
            'backHref' => route('tenants.index'),
            'backLabel' => trans('app.tenants.all_tenants'),
            'action' => route('tenants.store'),
            'method' => 'post',
            'submitLabel' => trans($continueToLease
                ? 'app.tenants.continue_to_lease'
                : 'app.tenants.create_tenant'),
            ...$form,
        ];
    }
}
