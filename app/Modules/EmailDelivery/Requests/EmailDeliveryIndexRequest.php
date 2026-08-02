<?php

namespace App\Modules\EmailDelivery\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailDeliveryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->query('search', '')),
            'status' => trim((string) $this->query('status', 'all')),
            'email_type' => trim((string) $this->query('email_type', 'all')),
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
            'status' => ['required', Rule::in(['all', 'processing', 'accepted', 'failed'])],
            'email_type' => ['required', 'string', 'max:80'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => $dateToRules,
            'per_page' => ['required', 'integer', Rule::in([10, 25, 50, 100])],
            'page' => ['required', 'integer', 'min:1'],
            'sort' => ['required', Rule::in(['created_at', 'status', 'email_type', 'recipient_email', 'attempts'])],
            'direction' => ['required', Rule::in(['asc', 'desc'])],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return $this->validated();
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
