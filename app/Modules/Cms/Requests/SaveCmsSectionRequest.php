<?php

namespace App\Modules\Cms\Requests;

use App\Modules\Cms\Support\CmsRules;
use Illuminate\Foundation\Http\FormRequest;

class SaveCmsSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->replace(CmsRules::normalizeSection($this->all()));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return CmsRules::section();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return CmsRules::attributes();
    }
}
