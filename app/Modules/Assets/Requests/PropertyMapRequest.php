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

        $this->merge([
            'portfolio_id' => in_array($portfolioId, [null, '', 'all'], true)
                ? null
                : $portfolioId,
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
        ];
    }

    public function portfolioId(): ?int
    {
        $portfolioId = $this->validated('portfolio_id');

        return $portfolioId === null ? null : (int) $portfolioId;
    }
}
