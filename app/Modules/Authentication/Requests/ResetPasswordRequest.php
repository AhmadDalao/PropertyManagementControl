<?php

namespace App\Modules\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }

    /** @return array{token:string,email:string,password:string,password_confirmation:string} */
    public function resetData(): array
    {
        return [
            'token' => $this->string('token')->toString(),
            'email' => $this->string('email')->toString(),
            'password' => (string) $this->input('password'),
            'password_confirmation' => (string) $this->input('password_confirmation'),
        ];
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
