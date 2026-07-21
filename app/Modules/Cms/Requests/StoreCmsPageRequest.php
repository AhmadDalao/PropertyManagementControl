<?php

namespace App\Modules\Cms\Requests;

use App\Modules\Cms\Requests\Concerns\PreparesCmsInput;
use App\Modules\Cms\Support\CmsOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCmsPageRequest extends FormRequest
{
    use PreparesCmsInput;

    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->normalizedSlug(),
            'title_en' => $this->requiredString('title_en'),
            'title_ar' => $this->requiredString('title_ar'),
            'excerpt_en' => $this->nullableString('excerpt_en'),
            'excerpt_ar' => $this->nullableString('excerpt_ar'),
            'seo_title_en' => $this->nullableString('seo_title_en'),
            'seo_title_ar' => $this->nullableString('seo_title_ar'),
            'seo_description_en' => $this->nullableString('seo_description_en'),
            'seo_description_ar' => $this->nullableString('seo_description_ar'),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'slug' => ['nullable', 'string', 'max:255', 'unique:cms_pages,slug'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'excerpt_en' => ['nullable', 'string', 'max:3000'],
            'excerpt_ar' => ['nullable', 'string', 'max:3000'],
            'seo_title_en' => ['nullable', 'string', 'max:255'],
            'seo_title_ar' => ['nullable', 'string', 'max:255'],
            'seo_description_en' => ['nullable', 'string', 'max:3000'],
            'seo_description_ar' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(CmsOptions::PAGE_STATUSES)],
            'is_homepage' => ['sometimes', 'boolean'],
            'is_visible' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return $this->cmsValidationAttributes();
    }
}
