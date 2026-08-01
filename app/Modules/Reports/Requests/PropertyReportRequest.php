<?php

namespace App\Modules\Reports\Requests;

use App\Models\Asset;
use App\Modules\Reports\Support\ReportFilterSet;
use Illuminate\Foundation\Http\FormRequest;

final class PropertyReportRequest extends FormRequest
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

    /** @return array{date_from:string,date_to:string,portfolio_id:int,property_id:int} */
    public function filters(Asset $property): array
    {
        return app(ReportFilterSet::class)->current([
            ...$this->validated(),
            'portfolio_id' => $property->portfolio_id,
            'property_id' => $property->id,
        ]);
    }
}
