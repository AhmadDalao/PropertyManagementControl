<?php

namespace App\Modules\Users\Support;

use Illuminate\Validation\ValidationException;

final class UserInputGuard
{
    /** @param array<string, mixed> $data */
    public function validate(array $data, bool $passwordRequired): void
    {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($name === '') {
            $errors['name'] = trans('validation.required', ['attribute' => trans('app.users.name')]);
        } elseif (mb_strlen($name) > 255) {
            $errors['name'] = trans('validation.max.string', [
                'attribute' => trans('app.users.name'),
                'max' => 255,
            ]);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            $errors['email'] = trans('validation.email', ['attribute' => trans('app.users.email')]);
        }

        if (! in_array($data['preferred_locale'] ?? null, UserOptions::LOCALES, true)) {
            $errors['preferred_locale'] = trans('validation.in', ['attribute' => trans('app.users.preferred_language')]);
        }

        if (! in_array($data['status'] ?? null, UserOptions::STATUSES, true)) {
            $errors['status'] = trans('validation.in', ['attribute' => trans('app.users.status')]);
        }

        if ($passwordRequired && mb_strlen($password) < 8) {
            $errors['password'] = trans('validation.min.string', [
                'attribute' => trans('app.users.temporary_password'),
                'min' => 8,
            ]);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
