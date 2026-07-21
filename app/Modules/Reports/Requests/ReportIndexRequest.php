<?php

namespace App\Modules\Reports\Requests;

use App\Modules\Reports\Support\ReportFilterSet;
use Illuminate\Foundation\Http\FormRequest;

class ReportIndexRequest extends FormRequest
{
    private bool $hasExplicitFilters = false;

    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->hasExplicitFilters = $this->query->has('date_from')
            || $this->query->has('date_to')
            || $this->query->has('portfolio_id');
        $portfolioId = $this->query('portfolio_id');

        $this->merge([
            'date_from' => trim((string) $this->query('date_from', now()->startOfYear()->toDateString())),
            'date_to' => trim((string) $this->query('date_to', now()->toDateString())),
            'portfolio_id' => in_array($portfolioId, [null, '', 'all'], true) ? null : $portfolioId,
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'portfolio_id' => ['nullable', 'integer', 'min:1', 'exists:portfolios,id'],
        ];
    }

    /** @return array{date_from:string,date_to:string,portfolio_id:int|null} */
    public function filters(): array
    {
        return app(ReportFilterSet::class)->current($this->validated());
    }

    public function hasExplicitFilters(): bool
    {
        return $this->hasExplicitFilters;
    }
}
