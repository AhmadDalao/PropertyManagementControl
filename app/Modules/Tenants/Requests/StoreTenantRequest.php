<?php

namespace App\Modules\Tenants\Requests;

use App\Models\User;
use App\Modules\Tenants\Support\TenantAccess;
use App\Modules\Tenants\Support\TenantOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTenantRequest extends FormRequest
{
    use HasTenantValidationAttributes;

    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof User
            && app(TenantAccess::class)->canManageSection($actor);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'portfolio_id' => [
                Rule::requiredIf($this->user()?->hasRole('superadmin') ?? false),
                'nullable',
                'integer',
                'exists:portfolios,id',
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_locale' => ['required', Rule::in(TenantOptions::LOCALES)],
            'password' => ['required', 'string', Password::defaults()],
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
