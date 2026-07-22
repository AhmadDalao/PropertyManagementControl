<?php

namespace App\Modules\Tenants\Requests;

trait HasTenantValidationAttributes
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'portfolio_id' => trans('app.tenants.portfolio'),
            'name' => trans('app.tenants.name'),
            'email' => trans('app.tenants.login_email'),
            'phone' => trans('app.tenants.phone'),
            'preferred_locale' => trans('app.tenants.portal_language'),
            'password' => trans('app.tenants.temporary_password'),
            'profile_type' => trans('app.tenants.profile_type'),
            'national_id' => trans('app.tenants.national_id'),
            'company_name' => trans('app.tenants.company_name'),
            'emergency_contact_name' => trans('app.tenants.emergency_contact_name'),
            'emergency_contact_phone' => trans('app.tenants.emergency_contact_phone'),
            'address' => trans('app.tenants.address'),
            'notes' => trans('app.tenants.notes'),
            'status' => trans('app.tenants.status'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $updates = [];

        foreach ([
            'name',
            'email',
            'phone',
            'national_id',
            'company_name',
            'emergency_contact_name',
            'emergency_contact_phone',
            'address',
            'notes',
        ] as $field) {
            if (is_string($this->input($field))) {
                $updates[$field] = trim((string) $this->input($field));
            }
        }

        if (isset($updates['email'])) {
            $updates['email'] = mb_strtolower($updates['email']);
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }
}
