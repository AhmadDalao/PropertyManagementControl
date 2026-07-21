<?php

namespace App\Modules\Cms\Requests;

use App\Modules\Cms\Requests\Concerns\PreparesCmsInput;
use App\Modules\Cms\Support\CmsOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveNavigationItemRequest extends FormRequest
{
    use PreparesCmsInput;

    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title_en' => $this->requiredString('title_en'),
            'title_ar' => $this->requiredString('title_ar'),
            'url' => $this->nullableString('url'),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:navigation_items,id'],
            'cms_page_id' => ['nullable', 'integer', 'exists:cms_pages,id'],
            'location' => ['required', Rule::in(CmsOptions::NAVIGATION_LOCATIONS)],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'target' => ['required', Rule::in(CmsOptions::NAVIGATION_TARGETS)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
        ];
    }
}
