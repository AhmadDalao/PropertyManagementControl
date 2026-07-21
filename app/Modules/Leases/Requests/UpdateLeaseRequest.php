<?php

namespace App\Modules\Leases\Requests;

use App\Models\Lease;
use App\Modules\Leases\Support\LeaseOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $lease = $this->route('lease');

        return $actor !== null
            && $lease instanceof Lease
            && $actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])
            && ($actor->hasRole('superadmin') || $actor->portfolio_id === $lease->portfolio_id);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(LeaseOptions::STATUSES)],
            'signed_at' => ['nullable', 'date'],
            'terms_en' => ['nullable', 'string', 'max:50000'],
            'terms_ar' => ['nullable', 'string', 'max:50000'],
            'notes' => ['nullable', 'string'],
            'resync_installments' => ['nullable', 'boolean'],
        ];
    }
}
