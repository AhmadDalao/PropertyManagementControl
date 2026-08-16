<?php

namespace App\Modules\ActionCenter\Requests;

use App\Modules\ActionCenter\Support\ActionCenterOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ActionCenterIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->query('search', '')),
            'type' => trim((string) $this->query('type', 'all')),
            'priority' => trim((string) $this->query('priority', 'all')),
            'assignee' => trim((string) $this->query('assignee', 'all')),
            'portfolio_id' => $this->nullableQueryId('portfolio_id'),
            'property_id' => $this->nullableQueryId('property_id'),
            'per_page' => $this->query('per_page', 6),
            'page' => $this->query('page', 1),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['required', Rule::in(['all', ...ActionCenterOptions::TYPES])],
            'priority' => ['required', Rule::in(['all', ...ActionCenterOptions::PRIORITIES])],
            'assignee' => ['required', 'string', 'regex:/^(all|me|unassigned|[1-9][0-9]*)$/'],
            'portfolio_id' => ['nullable', 'integer', 'min:1', 'exists:portfolios,id'],
            'property_id' => ['nullable', 'integer', 'min:1', 'exists:assets,id'],
            'per_page' => ['required', 'integer', Rule::in(ActionCenterOptions::PAGE_SIZES)],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{
     *     search:string,
     *     type:string,
     *     priority:string,
     *     assignee:string,
     *     portfolio_id:int|null,
     *     property_id:int|null,
     *     per_page:int,
     *     page:int
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'type' => (string) $validated['type'],
            'priority' => (string) $validated['priority'],
            'assignee' => (string) $validated['assignee'],
            'portfolio_id' => isset($validated['portfolio_id'])
                ? (int) $validated['portfolio_id']
                : null,
            'property_id' => isset($validated['property_id'])
                ? (int) $validated['property_id']
                : null,
            'per_page' => (int) $validated['per_page'],
            'page' => (int) $validated['page'],
        ];
    }

    private function nullableQueryId(string $key): mixed
    {
        $value = $this->query($key);

        return in_array($value, [null, '', 'all'], true) ? null : $value;
    }
}
