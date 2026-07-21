<?php

namespace App\Modules\Audit\Requests;

use App\Modules\Audit\Support\AuditSubjectRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->query('search', '')),
            'event' => trim((string) $this->query('event', 'all')),
            'subject_type' => trim((string) $this->query('subject_type', 'all')),
            'causer_id' => $this->nullableId($this->query('causer_id')),
            'portfolio_id' => $this->nullableId($this->query('portfolio_id')),
            'date_from' => $this->nullableText($this->query('date_from')),
            'date_to' => $this->nullableText($this->query('date_to')),
            'per_page' => (int) $this->query('per_page', 10),
            'page' => (int) $this->query('page', 1),
            'sort' => trim((string) $this->query('sort', 'created_at')),
            'direction' => strtolower(trim((string) $this->query('direction', 'desc'))),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $dateToRules = ['nullable', 'date_format:Y-m-d'];

        if ($this->filled('date_from')) {
            $dateToRules[] = 'after_or_equal:date_from';
        }

        return [
            'search' => ['nullable', 'string', 'max:200'],
            'event' => ['required', Rule::in(['all', 'created', 'updated', 'deleted'])],
            'subject_type' => ['required', Rule::in(['all', ...app(AuditSubjectRegistry::class)->aliases()])],
            'causer_id' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'portfolio_id' => ['nullable', 'integer', 'min:1', 'exists:portfolios,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => $dateToRules,
            'per_page' => ['required', 'integer', Rule::in([10, 25, 50, 100])],
            'page' => ['required', 'integer', 'min:1'],
            'sort' => ['required', Rule::in(['created_at', 'event', 'description'])],
            'direction' => ['required', Rule::in(['asc', 'desc'])],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => (string) ($validated['search'] ?? ''),
            'event' => (string) $validated['event'],
            'subject_type' => (string) $validated['subject_type'],
            'causer_id' => isset($validated['causer_id']) ? (int) $validated['causer_id'] : null,
            'portfolio_id' => isset($validated['portfolio_id']) ? (int) $validated['portfolio_id'] : null,
            'date_from' => (string) ($validated['date_from'] ?? ''),
            'date_to' => (string) ($validated['date_to'] ?? ''),
            'per_page' => (int) $validated['per_page'],
            'page' => (int) $validated['page'],
            'sort' => (string) $validated['sort'],
            'direction' => (string) $validated['direction'],
        ];
    }

    private function nullableId(mixed $value): mixed
    {
        return in_array($value, [null, '', 'all'], true) ? null : $value;
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
