<?php

namespace App\Modules\CompanyControl\Requests;

use App\Modules\CompanyControl\Support\CompanyControlOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CompanyControlIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->query('search', '')),
            'data_source' => trim((string) $this->query('data_source', 'live')),
            'status' => trim((string) $this->query('status', 'active')),
            'attention' => trim((string) $this->query('attention', 'all')),
            'sort' => trim((string) $this->query('sort', 'attention')),
            'direction' => trim((string) $this->query('direction', 'desc')),
            'per_page' => $this->query('per_page', 12),
            'page' => $this->query('page', 1),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'data_source' => ['required', Rule::in(CompanyControlOptions::DATA_SOURCES)],
            'status' => ['required', Rule::in(CompanyControlOptions::STATUSES)],
            'attention' => ['required', Rule::in(CompanyControlOptions::ATTENTION)],
            'sort' => ['required', Rule::in(CompanyControlOptions::SORTS)],
            'direction' => ['required', Rule::in(CompanyControlOptions::DIRECTIONS)],
            'per_page' => ['required', 'integer', Rule::in(CompanyControlOptions::PAGE_SIZES)],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{
     *   search:string,data_source:string,status:string,attention:string,
     *   sort:string,direction:string,per_page:int,page:int
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'data_source' => (string) $validated['data_source'],
            'status' => (string) $validated['status'],
            'attention' => (string) $validated['attention'],
            'sort' => (string) $validated['sort'],
            'direction' => (string) $validated['direction'],
            'per_page' => (int) $validated['per_page'],
            'page' => (int) $validated['page'],
        ];
    }
}
