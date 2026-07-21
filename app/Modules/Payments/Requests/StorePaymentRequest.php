<?php

namespace App\Modules\Payments\Requests;

use App\Modules\Payments\Support\PaymentOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'lease_id' => ['required', 'integer', 'exists:leases,id'],
            'type' => ['required', Rule::in(PaymentOptions::TYPES)],
            'method' => ['required', Rule::in(PaymentOptions::METHODS)],
            'status' => ['required', Rule::in(PaymentOptions::CREATE_STATUSES)],
            'reference' => ['nullable', 'string', 'max:255', 'unique:payments,reference'],
            'received_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
