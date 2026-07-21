<?php

namespace App\Modules\Maintenance\Requests;

use App\Modules\Maintenance\Support\MaintenanceOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager', 'tenant']) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        if ($this->user()?->hasRole('tenant')) {
            return [
                'asset_id' => ['required', 'integer', 'exists:assets,id'],
                'category' => ['required', Rule::in(MaintenanceOptions::CATEGORIES)],
                'priority' => ['required', Rule::in(MaintenanceOptions::PRIORITIES)],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
            ];
        }

        return [
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'tenant_profile_id' => ['required', 'integer', 'exists:tenant_profiles,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'category' => ['required', Rule::in(MaintenanceOptions::CATEGORIES)],
            'priority' => ['required', Rule::in(MaintenanceOptions::PRIORITIES)],
            'status' => ['required', Rule::in(MaintenanceOptions::STATUSES)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }
}
