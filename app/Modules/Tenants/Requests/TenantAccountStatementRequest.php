<?php

namespace App\Modules\Tenants\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TenantAccountStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date_from' => trim((string) $this->query('date_from', now()->startOfYear()->toDateString())),
            'date_to' => trim((string) $this->query('date_to', now()->toDateString())),
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    /** @return array{date_from:string,date_to:string} */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'date_from' => (string) $validated['date_from'],
            'date_to' => (string) $validated['date_to'],
        ];
    }
}
