<?php

namespace App\Modules\Reports\Requests;

use App\Modules\Reports\Support\ReportFilterSet;
use App\Modules\Reports\Support\ReportPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            || $this->query->has('portfolio_id')
            || $this->query->has('property_id')
            || $this->query->has('period');
        $portfolioId = $this->query('portfolio_id');
        $propertyId = $this->query('property_id');
        $period = trim((string) $this->query('period', ReportPeriod::CUSTOM));
        $periods = app(ReportPeriod::class);
        $dates = $periods->resolve(
            $period,
            $this->query('date_from', now()->startOfYear()->toDateString()),
            $this->query('date_to', now()->toDateString()),
        );

        $this->merge([
            'period' => $period,
            'date_from' => $dates['date_from'],
            'date_to' => $dates['date_to'],
            'portfolio_id' => in_array($portfolioId, [null, '', 'all'], true) ? null : $portfolioId,
            'property_id' => in_array($propertyId, [null, '', 'all'], true) ? null : $propertyId,
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'period' => ['required', Rule::in(app(ReportPeriod::class)->values())],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'portfolio_id' => ['nullable', 'integer', 'min:1', 'exists:portfolios,id'],
            'property_id' => ['nullable', 'integer', 'min:1', 'exists:assets,id'],
        ];
    }

    /** @return array{period:string,date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null} */
    public function filters(): array
    {
        return app(ReportFilterSet::class)->current($this->validated());
    }

    public function hasExplicitFilters(): bool
    {
        return $this->hasExplicitFilters;
    }
}
