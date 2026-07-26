<?php

namespace App\Modules\PortfolioControl\Requests;

use App\Modules\PortfolioControl\Support\PortfolioControlOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PortfolioControlIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([
            'superadmin',
            'owner',
            'property_manager',
        ]) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $portfolioId = $this->query('portfolio_id');

        $this->merge([
            'search' => trim((string) $this->query('search', '')),
            'attention' => trim((string) $this->query('attention', 'all')),
            'portfolio_id' => in_array($portfolioId, [null, '', 'all'], true)
                ? null
                : $portfolioId,
            'sort' => trim((string) $this->query('sort', 'attention')),
            'per_page' => $this->query('per_page', 12),
            'page' => $this->query('page', 1),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'attention' => [
                'required',
                Rule::in(PortfolioControlOptions::ATTENTION),
            ],
            'portfolio_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:portfolios,id',
            ],
            'sort' => [
                'required',
                Rule::in(PortfolioControlOptions::SORTS),
            ],
            'per_page' => [
                'required',
                'integer',
                Rule::in(PortfolioControlOptions::PAGE_SIZES),
            ],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{
     *     search:string,
     *     attention:string,
     *     portfolio_id:int|null,
     *     sort:string,
     *     per_page:int,
     *     page:int
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'attention' => (string) $validated['attention'],
            'portfolio_id' => isset($validated['portfolio_id'])
                ? (int) $validated['portfolio_id']
                : null,
            'sort' => (string) $validated['sort'],
            'per_page' => (int) $validated['per_page'],
            'page' => (int) $validated['page'],
        ];
    }
}
