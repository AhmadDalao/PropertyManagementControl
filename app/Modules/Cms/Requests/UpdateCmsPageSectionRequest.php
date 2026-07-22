<?php

namespace App\Modules\Cms\Requests;

use App\Modules\Cms\Support\CmsRules;
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
        return CmsRules::pageSection();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return CmsRules::attributes();
    }
}
