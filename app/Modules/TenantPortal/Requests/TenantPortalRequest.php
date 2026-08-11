<?php

namespace App\Modules\TenantPortal\Requests;

use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Payments\Support\PaymentOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TenantPortalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('tenant') === true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['search', 'status', 'type', 'date_from', 'date_to'] as $key) {
            $value = $this->query($key, '');
            $this->merge([$key => is_scalar($value) ? trim((string) $value) : $value]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['all', ...PaymentOptions::STATUSES])],
            'type' => ['nullable', Rule::in(['all', ...DocumentOptions::TYPES])],
            'lease_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', Rule::in([10, 25, 50])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function leaseId(): ?int
    {
        $value = $this->validated('lease_id');

        return $value ? (int) $value : null;
    }

    /** @return array{search:string,status:string,type:string,lease_id:?int,date_from:string,date_to:string,per_page:int} */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => (string) ($validated['search'] ?? ''),
            'status' => (string) (($validated['status'] ?? '') ?: 'all'),
            'type' => (string) (($validated['type'] ?? '') ?: 'all'),
            'lease_id' => isset($validated['lease_id']) ? (int) $validated['lease_id'] : null,
            'date_from' => (string) ($validated['date_from'] ?? ''),
            'date_to' => (string) ($validated['date_to'] ?? ''),
            'per_page' => (int) ($validated['per_page'] ?? 10),
        ];
    }
}
