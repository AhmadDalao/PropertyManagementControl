<?php

namespace App\Modules\Tenants\Requests;

use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Tenants\Support\TenantAccess;
use App\Modules\Tenants\Support\TenantOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateTenantRequest extends FormRequest
{
    use HasTenantValidationAttributes;

    public function authorize(): bool
    {
        $actor = $this->user();
        $tenant = $this->route('tenant');

        return $actor instanceof User
            && $tenant instanceof TenantProfile
            && app(TenantAccess::class)->canManage($actor, $tenant);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $tenant = $this->route('tenant');
        $userId = $tenant instanceof TenantProfile ? $tenant->user_id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_locale' => ['required', Rule::in(TenantOptions::LOCALES)],
            'password' => ['nullable', 'string', Password::defaults()],
            'profile_type' => ['required', Rule::in(TenantOptions::PROFILE_TYPES)],
            'national_id' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(TenantOptions::STATUSES)],
        ];
    }
}
