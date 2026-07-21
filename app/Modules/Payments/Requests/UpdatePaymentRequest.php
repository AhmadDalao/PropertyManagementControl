<?php

namespace App\Modules\Payments\Requests;

use App\Models\Payment;
use App\Modules\Payments\Support\PaymentOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $payment = $this->route('payment');

        return $actor !== null
            && $payment instanceof Payment
            && $actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])
            && ($actor->hasRole('superadmin') || $actor->portfolio_id === $payment->portfolio_id);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(PaymentOptions::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
