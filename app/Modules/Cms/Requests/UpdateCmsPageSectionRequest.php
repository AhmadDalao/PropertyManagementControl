<?php

namespace App\Modules\Cms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCmsPageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
            'settings_json' => ['sometimes', 'nullable', 'array', 'max:100'],
        ];
    }
}
