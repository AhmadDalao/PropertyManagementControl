<?php

namespace App\Modules\Cms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderCmsPageSectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer', 'distinct', 'exists:cms_page_sections,id'],
        ];
    }
}
