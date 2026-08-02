<?php

namespace App\Modules\DailyOperationsReports\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StartDailyOperationsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'portfolio_id' => $this->input('portfolio_id') ?: null,
        ]);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
        ];
    }

    public function portfolioId(): ?int
    {
        $value = $this->validated('portfolio_id');

        return $value ? (int) $value : null;
    }
}
