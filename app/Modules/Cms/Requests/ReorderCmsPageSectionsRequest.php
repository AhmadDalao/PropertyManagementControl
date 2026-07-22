<?php

namespace App\Modules\Cms\Requests;

use App\Modules\Cms\Support\CmsRules;
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
        return CmsRules::reorder();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return CmsRules::attributes();
    }

    /** @return array<int, int> */
    public function orderedIds(): array
    {
        $ids = $this->validated('ordered_ids');

        return is_array($ids) ? array_map('intval', $ids) : [];
    }
}
