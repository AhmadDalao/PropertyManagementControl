<?php

namespace App\Modules\Payments\Requests;

use App\Modules\Payments\Support\PaymentAccess;
use App\Modules\Payments\Support\PaymentOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePaymentRequest extends FormRequest
{
    use HasPaymentValidationAttributes;

    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor !== null && app(PaymentAccess::class)->canManageSection($actor);
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
            'amount' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,999999999999.99'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
