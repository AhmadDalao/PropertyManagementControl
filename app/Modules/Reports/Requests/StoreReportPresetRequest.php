<?php

namespace App\Modules\Reports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportPresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $filters = $this->input('filters_json');

        if (is_array($filters) && in_array($filters['portfolio_id'] ?? null, ['', 'all'], true)) {
            $filters['portfolio_id'] = null;
        }

        $this->merge([
            'resource' => 'portfolio-report',
            'filters_json' => is_array($filters) ? $filters : [],
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'resource' => ['required', Rule::in(['portfolio-report'])],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'visibility' => ['required', Rule::in(['private', 'portfolio', 'global'])],
            'is_default' => ['sometimes', 'boolean'],
            'filters_json' => ['present', 'array'],
            'filters_json.date_from' => ['nullable', 'date_format:Y-m-d'],
            'filters_json.date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:filters_json.date_from'],
            'filters_json.portfolio_id' => ['nullable', 'integer', 'min:1', 'exists:portfolios,id'],
        ];
    }
}
