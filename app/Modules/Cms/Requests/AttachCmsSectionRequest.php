<?php

namespace App\Modules\Cms\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachCmsSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'cms_section_id' => [
                'required',
                'integer',
                Rule::exists('cms_sections', 'id')->where(
                    fn (Builder $query): Builder => $query->where('status', '!=', 'archived'),
                ),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
        ];
    }
}
