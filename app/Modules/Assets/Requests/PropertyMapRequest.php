<?php

namespace App\Modules\Assets\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertyMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) === true;
    }

    protected function prepareForValidation(): void
    {
        $portfolioId = $this->query('portfolio_id');
        $propertyId = $this->query('property_id');

        $this->merge([
            'portfolio_id' => in_array($portfolioId, [null, '', 'all'], true)
                ? null
                : $portfolioId,
            'property_id' => in_array($propertyId, [null, '', 'all'], true)
                ? null
                : $propertyId,
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'property_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function portfolioId(): ?int
    {
        $portfolioId = $this->validated('portfolio_id');

        return $portfolioId === null ? null : (int) $portfolioId;
    }

    public function propertyId(): ?int
    {
        $propertyId = $this->validated('property_id');

        return $propertyId === null ? null : (int) $propertyId;
    }
}
