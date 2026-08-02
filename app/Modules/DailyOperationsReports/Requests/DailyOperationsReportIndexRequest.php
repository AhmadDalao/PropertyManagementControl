<?php

namespace App\Modules\DailyOperationsReports\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DailyOperationsReportIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => trim((string) $this->query('status', 'all')),
            'portfolio_id' => $this->nullableId($this->query('portfolio_id')),
            'date_from' => trim((string) $this->query('date_from', '')),
            'date_to' => trim((string) $this->query('date_to', '')),
            'page' => $this->query('page', 1),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['all', 'queued', 'running', 'completed', 'failed', 'pruned'])],
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array{status:string,portfolio_id:int|null,date_from:string,date_to:string} */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'status' => (string) $validated['status'],
            'portfolio_id' => isset($validated['portfolio_id']) ? (int) $validated['portfolio_id'] : null,
            'date_from' => (string) ($validated['date_from'] ?? ''),
            'date_to' => (string) ($validated['date_to'] ?? ''),
        ];
    }

    private function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_INT) ?: null;
    }
}
