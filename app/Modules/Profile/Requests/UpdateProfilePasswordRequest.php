<?php

namespace App\Modules\Profile\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateProfilePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $requiresCurrentPassword = ! $this->user()->force_password_reset;

        return [
            'current_password' => $requiresCurrentPassword
                ? ['required', 'string', 'current_password:web']
                : ['nullable', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'current_password' => trans('app.profile.current_password'),
            'password' => trans('app.profile.new_password'),
            'password_confirmation' => trans('app.profile.confirm_password'),
        ];
    }
}
