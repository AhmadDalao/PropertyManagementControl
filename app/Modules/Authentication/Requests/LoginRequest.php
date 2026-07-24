<?php

namespace App\Modules\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /** @return array{email:string,password:string} */
    public function credentials(): array
    {
        return [
            'email' => $this->string('email')->toString(),
            'password' => (string) $this->input('password'),
        ];
    }

    public function rememberLogin(): bool
    {
        return $this->boolean('remember');
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')).'|'.$this->ip(),
        );
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }
    }
}
