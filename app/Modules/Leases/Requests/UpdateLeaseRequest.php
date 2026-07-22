<?php

namespace App\Modules\Leases\Requests;

use App\Models\Lease;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Leases\Support\LeaseOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateLeaseRequest extends FormRequest
{
    use HasLeaseValidationAttributes;

    public function authorize(): bool
    {
        $actor = $this->user();
        $lease = $this->route('lease');

        return $actor !== null
            && $lease instanceof Lease
            && app(LeaseAccess::class)->canManage($actor, $lease);
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
            'notes' => ['nullable', 'string', 'max:50000'],
        ];
    }
}
