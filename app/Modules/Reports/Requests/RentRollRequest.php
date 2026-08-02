<?php

namespace App\Modules\Reports\Requests;

use App\Modules\Reports\Support\RentRollOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RentRollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $portfolioId = $this->query('portfolio_id');
        $propertyId = $this->query('property_id');

        $this->merge([
            'search' => trim((string) $this->query('search', '')),
            'state' => trim((string) $this->query('state', 'all')),
            'portfolio_id' => in_array($portfolioId, [null, '', 'all'], true) ? null : $portfolioId,
            'property_id' => in_array($propertyId, [null, '', 'all'], true) ? null : $propertyId,
            'per_page' => $this->query('per_page', 10),
            'page' => $this->query('page', 1),
            'sort' => $this->query(
                'sort',
                app()->isLocale('ar') ? 'title_ar' : 'title_en',
            ),
            'direction' => strtolower((string) $this->query('direction', 'asc')),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'state' => ['required', Rule::in(['all', ...RentRollOptions::STATES])],
            'portfolio_id' => ['nullable', 'integer', 'min:1', 'exists:portfolios,id'],
            'property_id' => ['nullable', 'integer', 'min:1', 'exists:assets,id'],
            'per_page' => ['required', 'integer', Rule::in([10, 25, 50, 100])],
            'page' => ['required', 'integer', 'min:1'],
            'sort' => ['required', Rule::in(['title_en', 'title_ar', 'code', 'asset_type'])],
            'direction' => ['required', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * @return array{
     *     search:string,
     *     state:string,
     *     portfolio_id:int|null,
     *     property_id:int|null,
     *     per_page:int,
     *     page:int,
     *     sort:string,
     *     direction:string
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => (string) ($validated['search'] ?? ''),
            'state' => (string) $validated['state'],
            'portfolio_id' => isset($validated['portfolio_id'])
                ? (int) $validated['portfolio_id']
                : null,
            'property_id' => isset($validated['property_id'])
                ? (int) $validated['property_id']
                : null,
            'per_page' => (int) $validated['per_page'],
            'page' => (int) $validated['page'],
            'sort' => (string) $validated['sort'],
            'direction' => (string) $validated['direction'],
        ];
    }
}
