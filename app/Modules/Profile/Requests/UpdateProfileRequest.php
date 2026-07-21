<?php

namespace App\Modules\Profile\Requests;

use App\Models\User;
use App\Modules\Users\Support\UserOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_locale' => ['required', Rule::in(UserOptions::LOCALES)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => trans('app.profile.name'),
            'phone' => trans('app.profile.phone'),
            'preferred_locale' => trans('app.profile.portal_language'),
        ];
    }

    /** @return array{name:string, phone:string|null, preferred_locale:string} */
    public function profileData(): array
    {
        $phone = $this->input('phone');

        return [
            'name' => $this->string('name')->toString(),
            'phone' => is_string($phone) && $phone !== '' ? $phone : null,
            'preferred_locale' => $this->string('preferred_locale')->toString(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $updates = [];

        foreach (['name', 'phone'] as $field) {
            if (is_string($this->input($field))) {
                $updates[$field] = trim((string) $this->input($field));
            }
        }

        if ($updates !== []) {
            $this->merge($updates);
        }
    }
}
