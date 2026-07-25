<?php

namespace App\Modules\Dashboard\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DashboardIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $propertyId = $this->query('property_id');

        $this->merge([
            'property_id' => in_array($propertyId, [null, '', 'all'], true)
                ? null
                : $propertyId,
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'property_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function propertyId(): ?int
    {
        $propertyId = $this->validated('property_id');

        return $propertyId === null ? null : (int) $propertyId;
    }
}
