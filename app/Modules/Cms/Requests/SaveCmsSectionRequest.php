<?php

namespace App\Modules\Cms\Requests;

use App\Modules\Cms\Requests\Concerns\PreparesCmsInput;
use App\Modules\Cms\Support\CmsOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCmsSectionRequest extends FormRequest
{
    use PreparesCmsInput;

    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name_en' => $this->requiredString('name_en'),
            'name_ar' => $this->requiredString('name_ar'),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'section_type' => ['required', Rule::in(CmsOptions::SECTION_TYPES)],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'content_en' => ['nullable', 'array', 'max:100'],
            'content_ar' => ['nullable', 'array', 'max:100'],
            'settings_json' => ['nullable', 'array', 'max:100'],
            'status' => ['required', Rule::in(CmsOptions::SECTION_STATUSES)],
        ];
    }
}
