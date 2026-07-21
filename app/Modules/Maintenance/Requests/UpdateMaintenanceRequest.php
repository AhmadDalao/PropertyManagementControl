<?php

namespace App\Modules\Maintenance\Requests;

use App\Models\MaintenanceRequest;
use App\Modules\Maintenance\Support\MaintenanceOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $request = $this->route('maintenanceRequest');

        if (! $actor || ! $request instanceof MaintenanceRequest) {
            return false;
        }

        if ($actor->hasRole('tenant')) {
            return $request->tenantProfile()->where('user_id', $actor->id)->exists();
        }

        return $actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])
            && ($actor->hasRole('superadmin') || $actor->portfolio_id === $request->portfolio_id);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        if ($this->user()?->hasRole('tenant')) {
            return ['comment' => ['required', 'string']];
        }

        return [
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['required', Rule::in(MaintenanceOptions::PRIORITIES)],
            'status' => ['required', Rule::in(MaintenanceOptions::STATUSES)],
            'internal_notes' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
            'is_public_comment' => ['nullable', 'boolean'],
        ];
    }
}
