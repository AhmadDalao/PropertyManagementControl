<?php

namespace App\Modules\Maintenance\Requests;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondToMaintenanceResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $request = $this->route('maintenanceRequest');

        return $actor?->hasRole('tenant')
            && $request instanceof MaintenanceRequest
            && $request->tenantProfile()->where('user_id', $actor->id)->exists();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'outcome' => ['required', Rule::in(['confirmed', 'reopen'])],
            'note' => ['nullable', 'string', 'max:5000', 'required_if:outcome,reopen'],
        ];
    }
}
