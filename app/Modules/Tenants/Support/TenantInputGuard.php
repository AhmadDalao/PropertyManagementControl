<?php

namespace App\Modules\Tenants\Support;

use Illuminate\Validation\ValidationException;

final class TenantInputGuard
{
    /** @param array<string, mixed> $data */
    public function validate(array $data, bool $passwordRequired): void
    {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($name === '') {
            $errors['name'] = trans('validation.required', ['attribute' => trans('app.tenants.name')]);
        } elseif (mb_strlen($name) > 255) {
            $errors['name'] = trans('validation.max.string', [
                'attribute' => trans('app.tenants.name'),
                'max' => 255,
            ]);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $errors['email'] = trans('validation.email', ['attribute' => trans('app.tenants.login_email')]);
        }

        foreach ($this->options() as $field => $option) {
            [$allowed, $attribute] = $option;

            if (! in_array($data[$field] ?? null, $allowed, true)) {
                $errors[$field] = trans('validation.in', ['attribute' => trans($attribute)]);
            }
        }

        if ($passwordRequired && mb_strlen($password) < 8) {
            $errors['password'] = trans('validation.min.string', [
                'attribute' => trans('app.tenants.temporary_password'),
                'min' => 8,
            ]);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** @return array<string, array{array<int, string>, string}> */
    private function options(): array
    {
        return [
            'preferred_locale' => [TenantOptions::LOCALES, 'app.tenants.portal_language'],
            'profile_type' => [TenantOptions::PROFILE_TYPES, 'app.tenants.profile_type'],
            'status' => [TenantOptions::STATUSES, 'app.tenants.status'],
        ];
    }
}
