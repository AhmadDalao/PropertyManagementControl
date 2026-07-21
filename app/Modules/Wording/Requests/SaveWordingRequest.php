<?php

namespace App\Modules\Wording\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveWordingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'english' => trim((string) $this->input('english')),
            'arabic' => trim((string) $this->input('arabic')),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:500'],
            'english' => ['required', 'string', 'max:2000'],
            'arabic' => ['required', 'string', 'max:2000'],
        ];
    }
}
