<?php

namespace App\Modules\Leases\Requests;

use App\Modules\Leases\Support\LeaseOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaseRequest extends FormRequest
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
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'tenant_profile_id' => ['required', 'integer', 'exists:tenant_profiles,id'],
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'status' => ['required', Rule::in(LeaseOptions::STATUSES)],
            'payment_frequency' => ['required', Rule::in(LeaseOptions::PAYMENT_FREQUENCIES)],
            'started_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:started_at'],
            'signed_at' => ['nullable', 'date'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'billing_day' => ['nullable', 'integer', 'between:1,31'],
            'terms_en' => ['nullable', 'string', 'max:50000'],
            'terms_ar' => ['nullable', 'string', 'max:50000'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
