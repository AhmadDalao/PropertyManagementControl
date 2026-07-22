<?php

namespace App\Modules\Users\Requests;

use App\Models\User;
use App\Modules\Users\Support\UserAccess;
use App\Modules\Users\Support\UserOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    use HasUserValidationAttributes;

    public function authorize(): bool
    {
        $actor = $this->user();
        $target = $this->route('user');

        return $actor instanceof User
            && $target instanceof User
            && app(UserAccess::class)->canManage($actor, $target);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $actor = $this->user();
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($target instanceof User ? $target->id : null),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'preferred_locale' => ['required', Rule::in(UserOptions::LOCALES)],
            'status' => ['required', Rule::in(UserOptions::STATUSES)],
            'password' => ['nullable', 'string', Password::defaults()],
            'role' => [
                'required',
                Rule::in(
                    $actor instanceof User && $target instanceof User
                        ? UserOptions::assignableRoles($actor, $target)
                        : [],
                ),
            ],
        ];
    }
}
