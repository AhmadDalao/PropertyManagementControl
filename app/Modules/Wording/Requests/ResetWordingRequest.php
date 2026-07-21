<?php

namespace App\Modules\Wording\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetWordingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:500'],
        ];
    }
}
