<?php

namespace App\Modules\Dashboard\Requests;

use App\Modules\Dashboard\Support\DashboardPeriod;
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
            'period' => DashboardPeriod::normalize(
                is_string($this->query('period')) ? $this->query('period') : null,
            ),
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'property_id' => ['nullable', 'integer', 'min:1'],
            'period' => ['required', 'in:'.implode(',', DashboardPeriod::VALUES)],
        ];
    }

    public function propertyId(): ?int
    {
        $propertyId = $this->validated('property_id');

        return $propertyId === null ? null : (int) $propertyId;
    }

    public function period(): string
    {
        return DashboardPeriod::normalize($this->validated('period'));
    }
}
