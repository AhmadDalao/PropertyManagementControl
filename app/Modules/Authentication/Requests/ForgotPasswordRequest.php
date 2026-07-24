<?php

namespace App\Modules\Authentication\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    public function email(): string
    {
        return $this->string('email')->toString();
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
