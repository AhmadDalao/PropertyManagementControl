<?php

namespace App\Modules\Tenants\Requests;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use App\Modules\Tenants\Support\TenantAccess;
use App\Modules\Tenants\Support\TenantOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreTenantRequest extends FormRequest
{
    use HasTenantValidationAttributes;

    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor instanceof User
            && app(TenantAccess::class)->canManageSection($actor)
            && app(AssignedPropertyScope::class)->hasAssignments($actor);
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
            'next' => ['nullable', 'string', Rule::in(['lease'])],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id', 'prohibited_unless:next,lease'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('asset_id') || ! $this->filled('asset_id')) {
                    return;
                }

                $actor = $this->user();
                $asset = Asset::query()->find($this->integer('asset_id'));

                if (
                    ! $actor instanceof User
                    || ! $asset instanceof Asset
                    || ! app(AssignedPropertyScope::class)->allowsAsset($actor, $asset)
                    || ($this->filled('portfolio_id') && $asset->portfolio_id !== $this->integer('portfolio_id'))
                ) {
                    $validator->errors()->add(
                        'asset_id',
                        trans('app.errors.property_assignment_access_denied'),
                    );
                }
            },
        ];
    }
}
