<?php

namespace App\Modules\Payments\Requests;

use App\Models\Payment;
use App\Modules\Payments\Support\PaymentAccess;
use App\Modules\Payments\Support\PaymentOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePaymentRequest extends FormRequest
{
    use HasPaymentValidationAttributes;

    public function authorize(): bool
    {
        $actor = $this->user();
        $payment = $this->route('payment');

        return $actor !== null
            && $payment instanceof Payment
            && app(PaymentAccess::class)->canManage($actor, $payment);
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
