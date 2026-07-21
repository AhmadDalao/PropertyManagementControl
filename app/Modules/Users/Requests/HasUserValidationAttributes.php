<?php

namespace App\Modules\Users\Requests;

trait HasUserValidationAttributes
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'portfolio_id' => trans('app.users.portfolio'),
            'name' => trans('app.users.name'),
            'email' => trans('app.users.email'),
            'phone' => trans('app.users.phone'),
            'preferred_locale' => trans('app.users.preferred_language'),
            'status' => trans('app.users.status'),
            'password' => trans('app.users.temporary_password'),
            'role' => trans('app.users.role'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $updates = [];

        foreach (['name', 'email', 'phone'] as $field) {
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
