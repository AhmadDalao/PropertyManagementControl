<?php

namespace App\Modules\Users\Requests;

use App\Modules\Users\Support\UserOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    use HasUserValidationAttributes;

    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $actor = $this->user();

        return [
            'portfolio_id' => [
                Rule::requiredIf(
                    ($actor?->hasRole('superadmin') ?? false)
                    && $this->input('role') !== 'superadmin',
                ),
                'nullable',
                'integer',
                'exists:portfolios,id',
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_locale' => ['required', Rule::in(UserOptions::LOCALES)],
            'status' => ['required', Rule::in(UserOptions::STATUSES)],
            'password' => ['required', 'string', Password::defaults()],
            'role' => ['required', Rule::in($actor ? UserOptions::assignableRoles($actor) : [])],
        ];
    }
}
