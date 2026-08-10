<?php

namespace App\Modules\InfrastructureSettings\Requests;

use App\Models\InfrastructureSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateInfrastructureSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'mail_enabled' => ['required', 'boolean'],
            'mail_host' => ['nullable', 'string', 'max:255', 'regex:/\A[^\s\/:]+\z/'],
            'mail_port' => ['nullable', 'integer', 'between:1,65535'],
            'mail_scheme' => ['nullable', Rule::in(['smtp', 'smtps'])],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => [
                'nullable',
                'string',
                'max:1024',
                'prohibited_if:clear_mail_password,true',
            ],
            'clear_mail_password' => ['required', 'boolean'],
            'mail_from_address' => ['nullable', 'email:rfc', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'scheduler_php_binary' => [
                'required',
                'string',
                'max:500',
                'regex:/\A\/[A-Za-z0-9._\/-]+\z/',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('mail_enabled')) {
                return;
            }

            foreach ([
                'mail_host',
                'mail_port',
                'mail_scheme',
                'mail_username',
                'mail_from_address',
                'mail_from_name',
            ] as $field) {
                if ($this->input($field) === null || trim((string) $this->input($field)) === '') {
                    $validator->errors()->add(
                        $field,
                        trans('app.infrastructure_settings.required_when_enabled'),
                    );
                }
            }

            $existingPassword = InfrastructureSetting::query()->first()?->mail_password;
            $passwordMissing = trim((string) $this->input('mail_password')) === ''
                && ($this->boolean('clear_mail_password') || ! $existingPassword);

            if ($passwordMissing) {
                $validator->errors()->add(
                    'mail_password',
                    trans('app.infrastructure_settings.password_required'),
                );
            }
        });
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $data = $this->validated();

        return [
            ...$data,
            'mail_enabled' => $this->boolean('mail_enabled'),
            'clear_mail_password' => $this->boolean('clear_mail_password'),
            'mail_password' => $this->nullableText($data['mail_password'] ?? null),
            'mail_host' => $this->nullableText($data['mail_host'] ?? null),
            'mail_username' => $this->nullableText($data['mail_username'] ?? null),
            'mail_from_address' => $this->nullableText($data['mail_from_address'] ?? null),
            'mail_from_name' => $this->nullableText($data['mail_from_name'] ?? null),
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attributes = trans('app.infrastructure_settings.attributes');

        return is_array($attributes) ? $attributes : [];
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
